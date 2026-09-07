<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the subscription_payment_logs table.
 *
 * This tracks every Cashfree payment attempt for platform subscriptions.
 * Completely separate from the tenant Payment model (which handles
 * business-level invoices, POS payments, etc.)
 *
 * Run: php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payment_logs', function (Blueprint $table) {
            $table->id();

            // Company that initiated the payment
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            // Plan being renewed (nullable in case plan is deleted later)
            $table->foreignId('plan_id')
                ->nullable()
                ->constrained('plans')
                ->nullOnDelete();

            $table->string('coupon_code', 50)->nullable();
            

            // ---------------------------------------------------------------
            // Cashfree Order Details
            // ---------------------------------------------------------------

            // Our generated order_id sent to Cashfree (e.g. "sub_12_1719400000")
            $table->string('cf_order_id', 50)->unique()->index();

            // Cashfree's internal payment ID (set after payment succeeds/fails)
            $table->string('cf_payment_id', 100)->nullable();

            // Cashfree's bank/gateway transaction ID
            $table->string('cf_transaction_id', 100)->nullable();

            // ---------------------------------------------------------------
            // Amount & Currency
            // ---------------------------------------------------------------

            $table->decimal('amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->nullable(); 
            $table->string('currency', 3)->default('INR');

            // ---------------------------------------------------------------
            // Status
            // Values: pending | paid | failed | refunded
            // ---------------------------------------------------------------

            $table->string('status', 20)->default('pending')->index();

            // Payment method used (upi, card, netbanking, wallet, etc.)
            $table->json('payment_method')->nullable();

            // Failure reason from Cashfree (if payment failed)
            $table->string('failure_reason', 255)->nullable();

            // Full Cashfree order/payment response stored for audit trail
            $table->json('gateway_response')->nullable();

            // Who initiated: 'web' (controller) or 'webhook' (async Cashfree event)
            $table->string('initiated_by', 20)->default('web');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payment_logs');
    }
};