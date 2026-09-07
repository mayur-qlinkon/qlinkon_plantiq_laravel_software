<?php

namespace App\Http\Controllers;

use App\Models\Platform\SubscriptionPaymentLog;
use App\Models\CompanySubscription;

use App\Services\Platform\CompanySubscriptionService;
use App\Services\CashfreeManager;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * CashfreeWebhookController
 *
 * Handles incoming Cashfree webhook events for the PLATFORM subscription layer.
 *
 * Route: POST /webhooks/cashfree/subscription  (CSRF-exempt)
 *
 * Purpose: Acts as a fallback for cases where the user closes the browser
 * before confirm() is called, or the return URL fails. Cashfree will POST
 * the payment event here asynchronously.
 *
 * Security:
 *   - Verifies HMAC-SHA256 signature before any processing
 *   - Idempotent: skips already-processed orders
 *   - Raw body must be read BEFORE any json_decode (Laravel does this correctly
 *     via getContent())
 */
class CashfreeWebhookController extends Controller
{
    public function __construct(
        protected CompanySubscriptionService $subscriptions
    ) {}

    /**
     * Handle POST /webhooks/cashfree/subscription
     */
    public function handle(Request $request)
    {
        $rawBody  = $request->getContent();
        $signature = $request->header('x-webhook-signature');
        $timestamp = $request->header('x-webhook-timestamp');

        // 1. Verify webhook signature
        $valid = CashfreeManager::forPlatform()->verifyWebhookSignature(
            $rawBody,
            (string) $signature,
            (string) $timestamp
        );

        if (! $valid) {
            Log::warning('[CashfreeWebhook] Invalid signature', [
                'signature' => $signature,
                'timestamp' => $timestamp,
                'ip'        => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // 2. Parse event
        $event   = $request->input('type');          // e.g. "PAYMENT_SUCCESS_WEBHOOK"
        $data    = $request->input('data', []);
        $orderId = $data['order']['order_id'] ?? null;

        Log::info('[CashfreeWebhook] Event received', [
            'type'     => $event,
            'order_id' => $orderId,
        ]);

        if (! $orderId) {
            return response()->json(['status' => 'ignored_no_order_id'], 200);
        }

        try {
            match (true) {
                str_contains($event, 'PAYMENT_SUCCESS') => $this->handlePaymentSuccess($data, $orderId),
                str_contains($event, 'PAYMENT_FAILED')  => $this->handlePaymentFailed($data, $orderId),
                default                                  => null, // Ignore unhandled events
            };

            return response()->json(['status' => 'ok'], 200);

        } catch (Throwable $e) {
            Log::error('[CashfreeWebhook] Processing error', [
                'event'    => $event,
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);

            // Return 200 to Cashfree anyway — returning 500 causes Cashfree to retry endlessly
            return response()->json(['status' => 'error_logged'], 200);
        }
    }

    // -------------------------------------------------------------------------
    // EVENT HANDLERS
    // -------------------------------------------------------------------------

    /**
     * Handle PAYMENT_SUCCESS_WEBHOOK.
     * Renews subscription if not already processed.
     */
    private function handlePaymentSuccess(array $data, string $orderId): void
    {
        $log = SubscriptionPaymentLog::where('cf_order_id', $orderId)->first();

        if (! $log) {
            // Order not initiated via our system — ignore
            Log::info('[CashfreeWebhook] Unknown order, skipping', ['order_id' => $orderId]);
            return;
        }

        // Idempotency: skip if already marked paid
        if ($log->status === 'paid') {
            Log::info('[CashfreeWebhook] Order already paid, skipping', ['order_id' => $orderId]);
            return;
        }

        $payment = $data['payment'] ?? [];

        $subscription = CompanySubscription::with('plan')
            ->where('company_id', $log->company_id)
            ->first();

        if (! $subscription) {
            Log::error('[CashfreeWebhook] No subscription found for company', [
                'company_id' => $log->company_id,
                'order_id'   => $orderId,
            ]);
            return;
        }

        DB::transaction(function () use ($log, $payment, $data, $subscription) {
            $log->update([
                'status'            => 'paid',
                'cf_payment_id'     => $payment['cf_payment_id'] ?? null,
                'cf_transaction_id' => $payment['bank_reference'] ?? null,
                'payment_method'    => $this->resolvePaymentMethod($payment),
                'gateway_response'  => $data,
                'initiated_by'      => 'webhook',
            ]);

            $this->subscriptions->renew($subscription);
        });

        Log::info('[CashfreeWebhook] Subscription renewed via webhook', [
            'company_id'    => $log->company_id,
            'order_id'      => $orderId,
            'cf_payment_id' => $payment['cf_payment_id'] ?? null,
        ]);
    }

    /**
     * Handle PAYMENT_FAILED_WEBHOOK.
     * Updates log status; subscription stays expired.
     */
    private function handlePaymentFailed(array $data, string $orderId): void
    {
        $log = SubscriptionPaymentLog::where('cf_order_id', $orderId)
            ->where('status', 'pending')
            ->first();

        if (! $log) {
            return;
        }

        $payment = $data['payment'] ?? [];

        $log->update([
            'status'           => 'failed',
            'cf_payment_id'    => $payment['cf_payment_id'] ?? null,
            'failure_reason'   => $payment['payment_message'] ?? null,
            'gateway_response' => $data,
            'initiated_by'     => 'webhook',
        ]);

        Log::warning('[CashfreeWebhook] Payment failed', [
            'order_id' => $orderId,
            'reason'   => $payment['payment_message'] ?? null,
        ]);
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    /**
     * Extract payment method label from Cashfree payment entity.
     * Cashfree nests it differently depending on the method used.
     */
    private function resolvePaymentMethod(array $payment): ?string
    {
        if (! empty($payment['payment_method'])) {
            $method = $payment['payment_method'];

            // Cashfree returns a nested object: { upi: {...} } or { card: {...} }
            if (is_array($method)) {
                return array_key_first($method); // 'upi', 'card', 'netbanking', etc.
            }

            return (string) $method;
        }

        return null;
    }
}