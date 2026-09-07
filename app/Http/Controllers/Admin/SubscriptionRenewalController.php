<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Platform\CompanySubscriptionService;
use App\Services\Platform\CashfreeManager;

use App\Models\Platform\SubscriptionPaymentLog;
use App\Models\CompanySubscription;
use App\Services\Promotion\PromotionService;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SubscriptionRenewalController
 *
 * Handles the subscription renewal flow via Cashfree PG.
 *
 * Flow:
 *   1. GET  /subscriptions           → index()      Show renewal page
 *   2. POST /subscription/renew/init → initiate()   Create Cashfree order, return session_id
 *   3. JS opens Cashfree Checkout (drop.js)
 *   4. POST /subscription/renew/confirm → confirm() Server-side fetch → verify → renew
 *
 * Security:
 *   - Frontend only receives payment_session_id (no credentials exposed)
 *   - confirm() fetches order status directly from Cashfree API (never trusts frontend)
 *   - Subscription renews only after PAID status confirmed
 *   - DB transaction ensures payment log + subscription update are atomic
 *   - Idempotency: duplicate cf_order_id attempts are blocked via unique index
 */
class SubscriptionRenewalController extends Controller
{
    public function __construct(
        protected CompanySubscriptionService $subscriptions,
        protected PromotionService $promotions
    ) {}

    // -------------------------------------------------------------------------
    // STEP 1: Subscription Page
    // -------------------------------------------------------------------------

    /**
     * Show the subscription expired / renewal page.
     */
    public function index()
    {
        $subscription = CompanySubscription::with('plan')
            ->where('company_id', Auth::user()->company_id)
            ->first();

        return view('errors.expired', [
            'subscription'     => $subscription,
            'currentPlan'      => $subscription?->plan,
            'cashfreeAppId'    => CashfreeManager::platformAppId(),
            'cashfreeEnv'      => CashfreeManager::jsEnvironment(),
        ]);
    }

    // -------------------------------------------------------------------------
    // STEP 1b: Validate a promo code (AJAX, inside the pre-payment modal)
    // -------------------------------------------------------------------------

    /**
     * Validate a promo code against the current plan's price and return the
     * computed discount. Does NOT record usage — that only happens after
     * payment success in confirm() (or immediately in initiate() for a
     * fully-covered free renewal). Frontend uses this to show "Applied:
     * -₹X, payable ₹Y" before the user commits to opening Cashfree.
     *
     * POST /subscription/renew/apply-coupon
     * Body: { coupon }
     * Response: { valid, discount, plan_amount, payable_amount } or { error }
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'coupon' => ['required', 'string', 'max:50'],
        ]);

        $companyId = Auth::user()->company_id;

        $subscription = CompanySubscription::with('plan')
            ->where('company_id', $companyId)
            ->first();

        if (! $subscription || ! $subscription->plan) {
            return response()->json(['error' => 'No plan found to renew.'], 422);
        }

        $planPrice = (float) $subscription->plan->price;

        // company_id: null → platform-level coupons (subscription plans are
        // sold by the platform, not by an individual tenant).
        $result = $this->promotions->apply($data['coupon'], $planPrice, null, null);

        if (! $result->valid) {
            return response()->json(['valid' => false, 'error' => $result->error], 422);
        }

        $discount      = round($result->discount, 2);
        $payableAmount = max(0, round($planPrice - $discount, 2));

        return response()->json([
            'valid'          => true,
            'discount'       => $discount,
            'plan_amount'    => $planPrice,
            'payable_amount' => $payableAmount,
        ]);
    }

    // -------------------------------------------------------------------------
    // STEP 2: Create Cashfree Order
    // -------------------------------------------------------------------------

    /**
     * Create a Cashfree order and return the payment_session_id to the frontend.
     *
     * POST /subscription/renew/init
     * Body: { coupon? }
     * Response: { payment_session_id, cf_order_id, amount, plan }
     *        or { free: true, redirect } when a coupon covers the full amount
     */
    public function initiate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'coupon' => ['nullable', 'string', 'max:50'],
        ]);

        $user      = Auth::user();
        $companyId = $user->company_id;

        $subscription = CompanySubscription::with('plan')
            ->where('company_id', $companyId)
            ->first();

        if (! $subscription || ! $subscription->plan) {
            return response()->json(['error' => 'No plan found to renew.'], 422);
        }

        $plan = $subscription->plan;

        if ((float) $plan->price <= 0) {
            return response()->json(['error' => 'This plan cannot be paid online.'], 422);
        }

        $planPrice  = (float) $plan->price;
        $couponCode = trim((string) ($data['coupon'] ?? ''));
        $discount   = 0.0;

        // Re-validate server-side even though applyCoupon() already checked
        // it — never trust a discount figure sent from the frontend.
        if ($couponCode !== '') {
            $result = $this->promotions->apply($couponCode, $planPrice, null, null);
            if (! $result->valid) {
                return response()->json(['error' => $result->error ?? 'Invalid promo code.'], 422);
            }
            $discount = round($result->discount, 2);
        }

        $payableAmount = round($planPrice - $discount, 2);

        // 🌟 FREE RENEWAL EDGE CASE: a coupon can discount the plan down to
        // ₹0. Cashfree does not accept ₹0 orders, so skip the payment
        // gateway entirely and renew directly.
        if ($payableAmount <= 0) {
            try {
                DB::transaction(function () use ($companyId, $plan, $couponCode, $discount, $planPrice, $subscription) {
                    SubscriptionPaymentLog::create([
                        'company_id'      => $companyId,
                        'plan_id'         => $plan->id,
                        'coupon_code'     => $couponCode !== '' ? $couponCode : null,
                        'cf_order_id'     => 'free_'.$companyId.'_'.now()->timestamp,
                        'amount'          => $planPrice,
                        'discount_amount' => $discount,
                        'currency'        => 'INR',
                        'status'          => 'paid',
                        'initiated_by'    => 'web',
                    ]);

                    $this->subscriptions->renew($subscription);

                    if ($couponCode !== '') {
                        $promotion = $this->promotions->findByCode($couponCode, null);
                        if ($promotion) {
                            // Mirrors PlanProvisioningService's exact pattern:
                            // find by code + recordUsage(), tagging WHICH
                            // tenant redeemed a platform-level coupon.
                            $promotion->recordUsage(
                                clientId: null,
                                discountAmount: $discount,
                                usable: $subscription,
                                companyId: $companyId
                            );
                        }
                    }
                });

                return response()->json([
                    'free'     => true,
                    'redirect' => route('admin.dashboard'),
                ]);
            } catch (Throwable $e) {
                Log::error('[Subscription] Free coupon renewal failed', [
                    'company_id' => $companyId,
                    'coupon'     => $couponCode,
                    'error'      => $e->getMessage(),
                ]);

                return response()->json(['error' => 'Could not process free renewal. Please try again.'], 500);
            }
        }

        // Generate a unique, stable order ID for this attempt
        // Format: sub_{companyId}_{timestamp} — safe for Cashfree (alphanumeric + _)
        $cfOrderId = 'sub_'.$companyId.'_'.now()->timestamp;

        try {
            $order = CashfreeManager::forPlatform()->createOrder(
                amount: $payableAmount,
                orderId: $cfOrderId,
                customerInfo: [
                    'id'    => (string) $companyId,
                    'name'  => $user->name ?? 'Customer',
                    'email' => $user->email ?? '',
                    'phone' => $user->phone ?? '9999999999',
                ],
                meta: [
                    'type'    => 'subscription_renewal',
                    'plan_id' => (string) $plan->id,
                ]
            );

            // Log the pending payment attempt immediately
            SubscriptionPaymentLog::create([
                'company_id'      => $companyId,
                'plan_id'         => $plan->id,
                'coupon_code'     => $couponCode !== '' ? $couponCode : null,
                'cf_order_id'     => $cfOrderId,
                'amount'          => $payableAmount,
                'discount_amount' => $discount,
                'currency'        => 'INR',
                'status'          => 'pending',
                'initiated_by'    => 'web',
            ]);

            // Only return session_id to frontend — no credentials, no secret keys
            return response()->json([
                'payment_session_id' => $order['payment_session_id'],
                'cf_order_id'        => $cfOrderId,
                'amount'             => $payableAmount,
                'plan'               => $plan->name,
            ]);

        } catch (Throwable $e) {
            Log::error('[Subscription] Cashfree order creation failed', [
                'company_id' => $companyId,
                'plan_id'    => $plan->id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Could not initialize payment. Please try again.'], 500);
        }
    }

    // -------------------------------------------------------------------------
    // STEP 3: Confirm Payment (never trust frontend)
    // -------------------------------------------------------------------------

    /**
     * Verify payment status by fetching directly from Cashfree API.
     * Renew subscription only after PAID status confirmed.
     *
     * POST /subscription/renew/confirm
     * Body: { cf_order_id }
     * Response: { success, redirect } or { error }
     */
    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cf_order_id' => ['required', 'string', 'max:50'],
        ]);

        $cfOrderId = $data['cf_order_id'];
        $companyId = Auth::user()->company_id;

        // Find the pending log created in initiate()
        $log = SubscriptionPaymentLog::where('cf_order_id', $cfOrderId)
            ->where('company_id', $companyId)
            ->where('status', 'pending')
            ->first();

        if (! $log) {
            // Either already processed (idempotent) or forged order_id
            $alreadyPaid = SubscriptionPaymentLog::where('cf_order_id', $cfOrderId)
                ->where('company_id', $companyId)
                ->where('status', 'paid')
                ->exists();

            if ($alreadyPaid) {
                // Already processed — return success (idempotent)
                return response()->json([
                    'success'  => true,
                    'redirect' => route('admin.dashboard'),
                ]);
            }

            Log::warning('[Subscription] Confirm called with unknown/mismatched order_id', [
                'cf_order_id' => $cfOrderId,
                'company_id'  => $companyId,
            ]);

            return response()->json(['error' => 'Invalid payment reference.'], 422);
        }

        try {
            // Fetch order status directly from Cashfree — never trust the frontend
            $cfOrder = CashfreeManager::forPlatform()->fetchOrder($cfOrderId);
            $cfStatus = $cfOrder['order_status'] ?? 'UNKNOWN';

            if ($cfStatus !== 'PAID') {
                // Update log to reflect the actual status
                $log->update([
                    'status'           => strtolower($cfStatus),
                    'gateway_response' => $cfOrder,
                    'failure_reason'   => $cfOrder['order_note'] ?? null,
                ]);

                Log::warning('[Subscription] Payment not confirmed', [
                    'cf_order_id' => $cfOrderId,
                    'cf_status'   => $cfStatus,
                    'company_id'  => $companyId,
                ]);

                return response()->json(['error' => 'Payment was not successful. Please try again.'], 422);
            }

            // Fetch payment details for full audit record
            $payments = CashfreeManager::forPlatform()->fetchOrderPayments($cfOrderId);
            $payment  = collect($payments)->firstWhere('payment_status', 'SUCCESS') ?? [];

            $subscription = CompanySubscription::with('plan')
                ->where('company_id', $companyId)
                ->firstOrFail();

            // Atomic: update log + renew subscription + redeem coupon in one transaction
            DB::transaction(function () use ($log, $payment, $cfOrder, $subscription) {
                $log->update([
                    'status'            => 'paid',
                    'cf_payment_id'     => $payment['cf_payment_id'] ?? null,
                    'cf_transaction_id' => $payment['bank_reference'] ?? null,
                    'payment_method'    => $payment['payment_method'] ?? null,
                    'gateway_response'  => $cfOrder,
                ]);

                $this->subscriptions->renew($subscription);

                // Redeem the coupon (if one was applied at initiate() time) —
                // only now, after Cashfree has confirmed PAID, never earlier.
                if ($log->coupon_code) {
                    $promotion = $this->promotions->findByCode($log->coupon_code, null);
                    if ($promotion) {
                        $promotion->recordUsage(
                            clientId: null,
                            discountAmount: (float) $log->discount_amount,
                            usable: $subscription,
                            companyId: $subscription->company_id
                        );
                    }
                }
            });            

            Log::info('[Subscription] Renewed via Cashfree', [
                'company_id'    => $companyId,
                'cf_order_id'   => $cfOrderId,
                'cf_payment_id' => $payment['cf_payment_id'] ?? null,
                'plan_id'       => $subscription->plan_id,
                'amount'        => $log->amount,
            ]);

            return response()->json([
                'success'  => true,
                'redirect' => route('admin.dashboard'),
            ]);

        } catch (Throwable $e) {
            Log::error('[Subscription] Confirm failed', [
                'cf_order_id' => $cfOrderId,
                'company_id'  => $companyId,
                'error'       => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Payment verification failed. Please contact support.'], 500);
        }
    }
}