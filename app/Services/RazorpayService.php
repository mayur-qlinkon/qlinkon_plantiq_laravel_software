<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class RazorpayService
{
    protected Api $api;

    protected string $keyId;

    /**
     * Dynamically initialize the Razorpay API.
     * By passing keys through the constructor, this service can process payments
     * for the Super Admin OR for a specific Tenant, depending on which keys you feed it!
     */
    public function __construct(string $keyId, string $keySecret)
    {
        if (empty($keyId) || empty($keySecret)) {
            Log::critical('[RazorpayService] Missing API Keys during initialization.');
            throw new Exception('Payment gateway is not configured properly.');
        }

        $this->keyId = $keyId;
        $this->api = new Api($keyId, $keySecret);
    }

    /**
     * Get the public Key ID (useful for passing to the frontend checkout modal)
     */
    public function getKeyId(): string
    {
        return $this->keyId;
    }

    /**
     * Create a new Razorpay Order.
     * Razorpay requires an Order ID before the frontend modal can open.
     */
    public function createOrder(float $amount, string $receiptId, array $notes = [], string $currency = 'INR')
    {
        try {
            // Razorpay expects the amount in the smallest currency sub-unit (Paise).
            // Multiply by 100 and cast to integer to prevent floating-point errors.
            $amountInPaise = (int) round($amount * 100);

            $orderData = [
                'receipt' => $receiptId,
                'amount' => $amountInPaise,
                'currency' => $currency,
                'payment_capture' => 1, // 1 = Auto-capture payment immediately
                'notes' => $notes,
            ];

            $razorpayOrder = $this->api->order->create($orderData);

            Log::info('[RazorpayService] Order Created Successfully', [
                'receipt_id' => $receiptId,
                'order_id' => $razorpayOrder['id'],
                'amount' => $amount,
            ]);

            return $razorpayOrder;

        } catch (Exception $e) {
            Log::error('[RazorpayService] Order Creation Failed', [
                'receipt_id' => $receiptId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Failed to initialize payment gateway. '.$e->getMessage());
        }
    }

    /**
     * Verify the cryptographic signature sent back by Razorpay.
     * This proves the payment actually happened and wasn't spoofed by a malicious user.
     */
    public function verifySignature(string $orderId, string $paymentId, string $signature): bool
    {
        try {
            $attributes = [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ];

            $this->api->utility->verifyPaymentSignature($attributes);

            return true;

        } catch (SignatureVerificationError $e) {
            Log::warning('[RazorpayService] Signature Verification Failed! Potential spoofing attempt.', [
                'order_id' => $orderId,
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Fetch a specific payment's details (useful for webhooks or manual checks)
     */
    public function fetchPayment(string $paymentId)
    {
        try {
            return $this->api->payment->fetch($paymentId);
        } catch (Exception $e) {
            Log::error('[RazorpayService] Failed to fetch payment details', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch a Razorpay order's details.
     */
    public function fetchOrder(string $orderId)
    {
        try {
            return $this->api->order->fetch($orderId);
        } catch (Exception $e) {
            Log::error('[RazorpayService] Failed to fetch order', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Verify a webhook signature: HMAC-SHA256 of the raw body with the webhook
     * secret. Use this in webhook controllers instead of inlining the check.
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $webhookSecret): bool
    {
        if (empty($webhookSecret) || empty($signature)) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $payload, $webhookSecret), $signature);
    }

    /**
     * Refund a payment (full or partial). $amount in main unit (rupees); null = full.
     */
    public function refund(string $paymentId, ?float $amount = null)
    {
        try {
            $data = $amount !== null ? ['amount' => (int) round($amount * 100)] : [];

            return $this->api->payment->fetch($paymentId)->refund($data);
        } catch (Exception $e) {
            Log::error('[RazorpayService] Refund failed', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Refund failed. '.$e->getMessage());
        }
    }
}
