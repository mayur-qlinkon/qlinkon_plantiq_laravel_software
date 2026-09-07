<?php

namespace App\Services\Platform;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CashfreeService
 *
 * Complete, DRY Cashfree PG implementation using Laravel HTTP Client.
 * No SDK. Works for Platform (subscription) AND Tenant (orders) contexts.
 *
 * Cashfree API version: 2023-08-01
 * Docs: https://docs.cashfree.com/reference/pg-new-apis-endpoint
 *
 * Key differences from Razorpay:
 *   - Amount in MAIN UNIT (INR), NOT paise
 *   - No signature verification from frontend — server fetches order status directly
 *   - Webhook signature: base64(HMAC-SHA256(timestamp + rawBody, secretKey))
 *   - JS SDK initialized with payment_session_id only (no app_id exposed to checkout)
 */
class CashfreeService
{
    protected string $appId;
    protected string $secretKey;
    protected string $baseUrl;
    protected string $apiVersion = '2023-08-01';

    public function __construct(string $appId, string $secretKey)
    {
        if (empty($appId) || empty($secretKey)) {
            Log::critical('[CashfreeService] Missing API credentials during initialization.');
            throw new Exception('Payment gateway is not configured properly.');
        }

        $this->appId     = $appId;
        $this->secretKey = $secretKey;
        $this->baseUrl   = config('services.cashfree.sandbox', true)
            ? 'https://sandbox.cashfree.com/pg'
            : 'https://api.cashfree.com/pg';
    }

    // -------------------------------------------------------------------------
    // CREDENTIAL ACCESS
    // -------------------------------------------------------------------------

    /**
     * Return the App ID (useful for passing to frontend JS SDK).
     */
    public function getAppId(): string
    {
        return $this->appId;
    }

    // -------------------------------------------------------------------------
    // ORDER CREATION
    // -------------------------------------------------------------------------

    /**
     * Create a Cashfree order.
     * Equivalent to Razorpay's createOrder().
     *
     * @param  float   $amount        Amount in INR (NOT paise — Cashfree uses main unit)
     * @param  string  $orderId       Your unique order ID (max 50 chars, alphanumeric + _ - .)
     * @param  array   $customerInfo  ['id', 'name', 'email', 'phone']
     * @param  array   $meta          Optional key-value metadata stored on the Cashfree order
     * @param  string  $currency      Default INR
     * @return array   Full Cashfree order response (includes payment_session_id)
     *
     * Usage:
     *   $order = CashfreeManager::forPlatform()->createOrder(
     *       amount: 999.00,
     *       orderId: 'sub_12_1719400000',
     *       customerInfo: ['id' => '12', 'name' => 'Ravi', 'email' => 'ravi@example.com', 'phone' => '9876543210'],
     *       meta: ['type' => 'subscription_renewal', 'plan_id' => '3']
     *   );
     *   $sessionId = $order['payment_session_id'];
     */
    public function createOrder(
        float $amount,
        string $orderId,
        array $customerInfo,
        array $meta = [],
        string $currency = 'INR'
    ): array {
        $payload = [
            'order_id'         => $orderId,
            'order_amount'     => round($amount, 2),   // Main unit — NOT paise
            'order_currency'   => $currency,
            'customer_details' => [
                'customer_id'    => (string) ($customerInfo['id'] ?? Str::uuid()),
                'customer_name'  => $customerInfo['name']  ?? 'Customer',
                'customer_email' => $customerInfo['email'] ?? '',
                'customer_phone' => $customerInfo['phone'] ?? '9999999999',
            ],
            'order_meta' => $meta,
        ];

        $response = $this->http()->post('/orders', $payload);

        if (! $response->successful()) {
            Log::error('[CashfreeService] Order creation failed', [
                'order_id' => $orderId,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
            throw new Exception('Failed to create payment order: '.$response->json('message', 'Unknown error'));
        }

        Log::info('[CashfreeService] Order created', [
            'order_id'        => $orderId,
            'amount'          => $amount,
            'cf_order_id'     => $response->json('cf_order_id'),
            'payment_session' => $response->json('payment_session_id'),
        ]);

        return $response->json();
    }

    // -------------------------------------------------------------------------
    // ORDER STATUS FETCH (replaces signature verification)
    // -------------------------------------------------------------------------

    /**
     * Fetch full order details from Cashfree.
     * Use this instead of trusting any frontend response.
     *
     * Equivalent to Razorpay's fetchOrder().
     *
     * @return array  Cashfree order entity (includes order_status: PAID|ACTIVE|EXPIRED)
     *
     * Usage:
     *   $order = CashfreeManager::forPlatform()->fetchOrder('sub_12_1719400000');
     *   if ($order['order_status'] === 'PAID') { ... }
     */
    public function fetchOrder(string $orderId): array
    {
        $response = $this->http()->get("/orders/{$orderId}");

        if (! $response->successful()) {
            Log::error('[CashfreeService] Order fetch failed', [
                'order_id' => $orderId,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
            throw new Exception('Could not verify payment status for order: '.$orderId);
        }

        return $response->json();
    }

    /**
     * Fetch the list of payments for an order.
     * Returns array of payment entities with cf_payment_id, payment_status, bank_reference, etc.
     *
     * Equivalent to Razorpay's fetchPayment() but scoped to an order.
     *
     * Usage:
     *   $payments = CashfreeManager::forPlatform()->fetchOrderPayments('sub_12_1719400000');
     *   $success  = collect($payments)->firstWhere('payment_status', 'SUCCESS');
     */
    public function fetchOrderPayments(string $orderId): array
    {
        $response = $this->http()->get("/orders/{$orderId}/payments");

        if (! $response->successful()) {
            Log::error('[CashfreeService] Order payments fetch failed', [
                'order_id' => $orderId,
                'status'   => $response->status(),
            ]);
            throw new Exception('Could not fetch payment details for order: '.$orderId);
        }

        return $response->json();
    }

    /**
     * Convenience: returns true only if the order_status is PAID.
     * Use in confirm() controllers as a quick boolean check.
     *
     * Usage:
     *   if (! CashfreeManager::forPlatform()->isOrderPaid($cfOrderId)) {
     *       return response()->json(['error' => 'Payment not confirmed.'], 422);
     *   }
     */
    public function isOrderPaid(string $orderId): bool
    {
        try {
            $order = $this->fetchOrder($orderId);
            return ($order['order_status'] ?? '') === 'PAID';
        } catch (Exception $e) {
            Log::warning('[CashfreeService] isOrderPaid check failed', [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }

    // -------------------------------------------------------------------------
    // WEBHOOK SIGNATURE VERIFICATION
    // -------------------------------------------------------------------------

    /**
     * Verify a Cashfree webhook signature.
     *
     * Cashfree signature formula:
     *   base64( HMAC-SHA256( timestamp + rawBody, secretKey ) )
     *
     * Headers sent by Cashfree:
     *   x-webhook-signature  → the computed signature
     *   x-webhook-timestamp  → Unix timestamp in seconds
     *
     * Equivalent to Razorpay's verifyWebhookSignature() — but signature
     * is verified using THIS instance's secretKey, so the correct Manager
     * context must be used (platform vs company).
     *
     * @param  string $rawBody    Raw POST body — do NOT json_decode before passing
     * @param  string $signature  Value of x-webhook-signature header
     * @param  string $timestamp  Value of x-webhook-timestamp header
     *
     * Usage (platform webhook):
     *   $valid = CashfreeManager::forPlatform()->verifyWebhookSignature(
     *       $request->getContent(),
     *       $request->header('x-webhook-signature'),
     *       $request->header('x-webhook-timestamp')
     *   );
     *
     * Usage (tenant webhook):
     *   $valid = CashfreeManager::forCompany($company)->verifyWebhookSignature(...);
     */
    public function verifyWebhookSignature(string $rawBody, string $signature, string $timestamp): bool
    {
        if (empty($signature) || empty($timestamp)) {
            return false;
        }

        $data     = $timestamp . $rawBody;
        $computed = base64_encode(hash_hmac('sha256', $data, $this->secretKey, true));

        return hash_equals($computed, $signature);
    }

    // -------------------------------------------------------------------------
    // REFUNDS
    // -------------------------------------------------------------------------

    /**
     * Initiate a full or partial refund.
     * Equivalent to Razorpay's refund().
     *
     * @param  string     $orderId   Your order ID
     * @param  string     $refundId  Unique refund ID (you generate, max 40 chars)
     * @param  float|null $amount    Partial refund in INR; null = full order amount
     * @param  string     $note      Refund note shown in Cashfree dashboard
     *
     * Usage:
     *   CashfreeManager::forPlatform()->refund('sub_12_1719400000', 'ref_12_'.time());
     *   CashfreeManager::forCompany($company)->refund('ord_456_1719500000', 'ref_456_'.time(), 250.00);
     */
    public function refund(string $orderId, string $refundId, ?float $amount = null, string $note = 'Refund'): array
    {
        if ($amount === null) {
            $order  = $this->fetchOrder($orderId);
            $amount = (float) ($order['order_amount'] ?? 0);
        }

        $payload = [
            'refund_amount' => round($amount, 2),
            'refund_id'     => $refundId,
            'refund_note'   => $note,
        ];

        $response = $this->http()->post("/orders/{$orderId}/refunds", $payload);

        if (! $response->successful()) {
            Log::error('[CashfreeService] Refund failed', [
                'order_id'  => $orderId,
                'refund_id' => $refundId,
                'error'     => $response->body(),
            ]);
            throw new Exception('Refund failed: '.$response->json('message', 'Unknown error'));
        }

        Log::info('[CashfreeService] Refund initiated', [
            'order_id'  => $orderId,
            'refund_id' => $refundId,
            'amount'    => $amount,
        ]);

        return $response->json();
    }

    // -------------------------------------------------------------------------
    // INTERNAL
    // -------------------------------------------------------------------------

    /**
     * Pre-configured HTTP client with Cashfree auth headers.
     * All methods reuse this — single source of truth for headers.
     */
    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'x-client-id'     => $this->appId,
                'x-client-secret' => $this->secretKey,
                'x-api-version'   => $this->apiVersion,
                'Content-Type'    => 'application/json',
                'Accept'          => 'application/json',
            ])
            ->timeout(30);
    }
}