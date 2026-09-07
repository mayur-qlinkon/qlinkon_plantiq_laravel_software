<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RazorpayWebhookController extends Controller
{
    /**
     * Handle incoming Razorpay Webhooks dynamically for any tenant.
     * POST /{slug}/webhooks/razorpay
     */
    public function handle(Request $request, string $slug)
    {
        // 1. Find the Tenant
        $company = Company::where('slug', $slug)->first();
        if (! $company) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        // 2. Fetch the Tenant's specific Webhook Secret from settings
        $webhookSecret = get_setting('razorpay_webhook_secret', null, $company->id);

        if (! $webhookSecret) {
            Log::error("[Webhook] Missing webhook secret for company: {$slug}");

            return response()->json(['error' => 'Webhook not configured'], 400);
        }

        // 3. Verify the Webhook Signature
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');

        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning("[Webhook] Invalid signature attempt for company: {$slug}");

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // 4. Process the Event
        $event = $request->input('event');
        $payloadData = $request->input('payload');

        Log::info("[Webhook] Received verified event: {$event} for {$slug}");

        try {
            switch ($event) {
                case 'payment.captured':
                case 'order.paid':
                    $this->handlePaymentCaptured($payloadData, $company->id);
                    break;

                case 'payment.failed':
                    // Handle failed recurring payments or logged attempts
                    break;

                    // You can add more events here later (like subscription.charged)
            }

            return response()->json(['status' => 'success'], 200);

        } catch (\Throwable $e) {
            Log::error("[Webhook] Error processing {$event}: ".$e->getMessage());

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Business Logic: Mark the order as paid
     */
    private function handlePaymentCaptured(array $payloadData, int $companyId)
    {
        // Razorpay nests the order ID differently depending on the event,
        // but it's usually inside payload.payment.entity.order_id
        $razorpayOrderId = $payloadData['payment']['entity']['order_id'] ?? null;

        if (! $razorpayOrderId) {
            return;
        }

        // Find the order that matches this Razorpay Order ID
        // Note: You'll need to make sure you are saving the 'razorpay_order_id'
        // into your database when you generate it in the OrderController!
        $order = Order::where('company_id', $companyId)
            ->where('razorpay_order_id', $razorpayOrderId)
            ->first();

        if ($order && $order->payment_status !== 'paid') {
            $order->update([
                'payment_status' => 'paid',
                // You can also save the payment ID for refunds later
                'transaction_id' => $payloadData['payment']['entity']['id'],
            ]);

            Log::info("[Webhook] Order {$order->order_number} marked as PAID via webhook.");
        }
    }
}
