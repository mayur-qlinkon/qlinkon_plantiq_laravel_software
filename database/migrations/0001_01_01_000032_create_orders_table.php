<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {

            $table->id();

            // ── Tenant isolation ──
            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();  
            // ── Fulfillment links ──
            $table->foreignId('store_id')
                ->nullable()
                ->constrained('stores')
                ->nullOnDelete();

            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete();          

            // ── Order number — COMPANY SCOPED unique, not global ──
            // ORD-202401-00042 per company — two companies can have same number
            $table->string('order_number', 30);
            $table->unique(['company_id', 'order_number']); // ← scoped, not global

            // ── Order type ──
            $table->enum('order_type', [
                'retail',       // regular B2C storefront order
                'wholesale',    // bulk B2B order
                'inquiry',      // pure inquiry, no buy commitment
                'sample',       // sample request
                'subscription', // recurring (future)
                'repair',       // service/repair (future)
            ])->default('retail');

            // ── Source ──
            $table->enum('source', [
                'storefront',
                'whatsapp',
                'admin',
                'pos',
                'api',
            ])->default('storefront');

            // ── Order status ──
            $table->enum('status', [
                'inquiry',
                'confirmed',
                'processing',
                'shipped',
                'out_for_delivery',
                'delivered',
                'cancelled',
                'refunded',
                'failed',
            ])->default('inquiry');

            // ── Customer info ──
            $table->string('customer_name', 100);
            $table->string('customer_phone', 15);
            $table->string('customer_email', 150)->nullable();
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // ── Delivery address ──
            $table->string('delivery_address')->nullable();
            $table->string('delivery_city', 80)->nullable();
            $table->string('delivery_state', 80)->nullable();
            $table->string('delivery_pincode', 10)->nullable();
            $table->string('delivery_country', 60)->default('India');

            // ── GST supply state ──
            // delivery_state == company state → intra-state → CGST + SGST
            // delivery_state != company state → inter-state → IGST
            $table->string('supply_state', 80)->nullable();

            // ── Pricing ──
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);

            // ── GST breakdown — Indian critical ──
            $table->decimal('cgst_amount', 10, 2)->default(0);
            $table->decimal('sgst_amount', 10, 2)->default(0);
            $table->decimal('igst_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0); // sum of above 3

            $table->decimal('shipping_amount', 10, 2)->default(0);

            // ── Round off — Indian accounting standard ──
            // Range: -0.49 to +0.50 — invoice totals round to nearest rupee
            $table->decimal('round_off', 5, 2)->default(0);

            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('refunded_amount', 10, 2)->default(0);

            $table->string('currency', 5)->default('INR');

            // ── Coupon ──
            $table->string('coupon_code', 30)->nullable();
            $table->decimal('coupon_discount', 10, 2)->default(0);

            // ── Payment ──
            $table->string('payment_method')->nullable();

            $table->string('payment_status', 30)->default('pending');

            // ── Payment table link ──
            $table->foreignId('payment_id')
                ->nullable()
                ->constrained('payments')
                ->nullOnDelete();

            // ── Razorpay fields — nullable until used ──
            $table->string('razorpay_order_id', 100)->nullable()->index();
            $table->string('razorpay_payment_id', 100)->nullable()->index();
            $table->string('razorpay_signature', 255)->nullable();
            $table->timestamp('paid_at')->nullable();
        

            // ── Delivery ──
            $table->enum('delivery_type', [
                'delivery',
                'pickup',
                'digital',
            ])->default('delivery');

            $table->string('tracking_number', 100)->nullable();
            $table->string('courier_name', 80)->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->date('expected_delivery_date')->nullable();

            // ── Communication ──
            $table->boolean('whatsapp_sent')->default(false);
            $table->boolean('confirmation_sms_sent')->default(false);
            $table->timestamp('last_notified_at')->nullable();

            // ── Notes ──
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('cancellation_reason')->nullable();

            // ── Denormalized counts — avoid COUNT() on listing ──
            $table->unsignedSmallInteger('items_count')->default(0);
            $table->unsignedSmallInteger('items_qty')->default(0);

            // ── Invoice link — when order is converted to tax invoice ──
            $table->foreignId('invoice_id')
                ->nullable()                
                ->nullOnDelete();

            // ── Audit ──
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ── Indexes ──
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'order_type']);
            $table->index(['company_id', 'created_at']);
            $table->index(['company_id', 'payment_status']);
            $table->index(['customer_phone']);
            $table->index(['source']);
            $table->index(['payment_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
