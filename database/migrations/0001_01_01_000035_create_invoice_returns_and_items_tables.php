<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. INVOICE RETURNS TABLE (Credit Notes Header)
        Schema::create('invoice_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();

            // 🌟 Crucial: Where is the stock going back to?
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();

            // 🌟 Crucial: Which invoice does this belong to?
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();

            $table->foreignId('customer_id')->nullable()->constrained('clients')->restrictOnDelete();
            $table->string('customer_name')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('salesperson_id')->nullable()->constrained('users');
            $table->unsignedBigInteger('pos_terminal_id')->nullable()->index();

            // E.g., CN-2603-0001 (Credit Note)
            $table->string('credit_note_number', 50);
            $table->enum('source', ['pos', 'direct', 'online'])->default('direct');
            $table->date('return_date');
            $table->decimal('max_returnable_qty', 10, 4)->nullable();
            $table->enum('return_type', ['refund', 'credit_note', 'replacement'])
                ->default('refund')
                ->comment('How return is handled');
            $table->decimal('refunded_amount', 15, 2)->default(0);
            $table->enum('return_reason', [
                'damaged',
                'expired',
                'wrong_item',
                'customer_return',
                'quality_issue',
                'other',
            ])->nullable();
            $table->boolean('restock')->default(true);
            $table->boolean('stock_updated')->default(false);

            // Indian GST Context (Copied from Original Invoice)
            $table->string('supply_state', 100);
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->enum('gst_treatment', ['registered', 'unregistered', 'composition', 'overseas', 'sez'])->default('unregistered');

            $table->string('currency_code', 3)->default('INR');
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);

            // Financials (These represent the NEGATIVE value to be deducted)
            $table->decimal('subtotal', 15, 4)->default(0);
            
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('discount_value', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);

            $table->decimal('taxable_amount', 15, 4)->default(0);

            $table->decimal('cgst_amount', 15, 4)->default(0);
            $table->decimal('sgst_amount', 15, 4)->default(0);
            $table->decimal('igst_amount', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);

            $table->decimal('shipping_charge', 15, 4)->default(0);
            $table->decimal('other_charges', 15, 4)->default(0);
            $table->decimal('round_off', 8, 2)->default(0);

            // Final Refundable Amount
            $table->decimal('grand_total', 15, 2)->default(0);

            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft')->index();

            // Replaces "payment_status". Tracks if we gave the customer their money back or adjusted their ledger.
            $table->enum('refund_status', ['unrefunded', 'partial', 'refunded'])->default('unrefunded')->index();

            $table->string('irn', 100)->nullable(); // In case a Credit Note needs E-Invoicing

            $table->text('notes')->nullable(); // "Items damaged in transit"
            $table->text('terms_conditions')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'invoice_id']);
            $table->unique(['company_id', 'credit_note_number']);
            $table->index(['company_id', 'return_date']);
        });

        Schema::create('invoice_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_return_id')->constrained()->cascadeOnDelete();

            // 🌟 Crucial: Link to the exact line item on the original invoice
            $table->foreignId('invoice_item_id')->constrained()->restrictOnDelete();

            // Snapshots
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_sku_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();

            $table->string('product_name');
            $table->string('hsn_code', 50)->nullable();

            // 🌟 This is the RETURNED quantity, not the original quantity
            $table->decimal('quantity', 10, 4);
            $table->decimal('unit_price', 15, 4);
            $table->boolean('is_restocked')->default(true);

            $table->enum('tax_type', ['inclusive', 'exclusive'])->default('exclusive');
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('discount_amount', 15, 4)->default(0);

            $table->decimal('taxable_value', 15, 4)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);

            $table->decimal('cgst_amount', 15, 4)->default(0);
            $table->decimal('sgst_amount', 15, 4)->default(0);
            $table->decimal('igst_amount', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);

            $table->decimal('total_amount', 15, 4)->default(0);

            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_returns');
        Schema::dropIfExists('invoice_return_items');
    }
};
