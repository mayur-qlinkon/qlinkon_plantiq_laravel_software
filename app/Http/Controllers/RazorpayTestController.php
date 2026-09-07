<?php

namespace App\Http\Controllers;

use App\Services\RazorpayService;
use Illuminate\Http\Request;

class RazorpayTestController extends Controller
{
    // ── Replace these with your Razorpay TEST mode keys ──
    private $testKeyId = 'rzp_test_RiIVAOjtlNfEVU';

    private $testSecret = 'Ba66D20j6P6bduAs6wPAh16d';

    public function index()
    {
        // 1. Spin up Pillar 1 (The Service)
        $rzp = new RazorpayService($this->testKeyId, $this->testSecret);

        // 2. Create a dummy order for ₹1.00 (1 rupee)
        $order = $rzp->createOrder(1.00, 'TEST_RECEIPT_'.rand(1000, 9999));

        // 3. Pass the data to our dummy blade
        return view('tools.razorpay-test', [
            'keyId' => $this->testKeyId,
            'orderId' => $order['id'],
            'internalOrder' => 'DUMMY-'.rand(100, 999),
        ]);
    }

    public function verify(Request $request)
    {
        // 1. Spin up the Service again to verify the signature
        $rzp = new RazorpayService($this->testKeyId, $this->testSecret);

        $isValid = $rzp->verifySignature(
            $request->razorpay_order_id,
            $request->razorpay_payment_id,
            $request->razorpay_signature
        );

        if ($isValid) {
            return response("<h1>🎉 SUCCESS!</h1> <p>Payment Verified. Order: {$request->order_number}</p> <p>Payment ID: {$request->razorpay_payment_id}</p>");
        }

        return response('<h1>❌ FAILED!</h1> <p>Invalid signature. Spoofing attempt detected.</p>', 400);
    }
}
