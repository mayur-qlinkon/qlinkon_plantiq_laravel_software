<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. INVOICES TABLE (The Header)
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')
                  ->nullable()                  
                  ->constrained('orders')
                  ->nullOnDelete();

            $table->foreignId('customer_id')->nullable()->constrained('clients')->restrictOnDelete();
            $table->string('customer_name')->nullable();
            $table->foreignId('created_by')->constrained('users');
            
            $table->foreignId('salesperson_id')->nullable()->constrained('users');
            $table->unsignedBigInteger('pos_terminal_id')->nullable()->index();

            $table->string('invoice_number', 50);

            // 🌟 The Unified Identifier
            $table->enum('source', ['pos', 'direct', 'online'])->default('direct');

            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            
            $table->string('supply_state', 100);
            $table->enum('gst_treatment', ['registered', 'unregistered', 'composition', 'overseas', 'sez'])->default('unregistered');
        
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            
            $table->string('currency_code', 3)->default('INR');
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);
            
            $table->decimal('shipping_tax_rate', 5, 2)->default(0);
            $table->decimal('shipping_tax_amount', 15, 4)->default(0);

            // Financials
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

            // Final Payable amounts
            $table->decimal('grand_total', 15, 2)->default(0);
            
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft')->index();
            $table->string('payment_status', 30)->default('unpaid')->index();
            $table->enum('settlement_status', ['open', 'closed'])
                ->default('open')                
                ->index();

            // Indian E-Invoicing & E-Way Bill
            $table->string('irn', 100)->nullable();
            $table->string('ack_no', 50)->nullable();
            $table->dateTime('ack_date')->nullable();
            $table->string('eway_bill_number', 50)->nullable();

            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'invoice_number']);
            $table->index(['company_id', 'invoice_date']);
        });

        // 2. INVOICE ITEMS TABLE (The Ledger lines)
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();

            $table->foreignId('product_id')->constrained();
            $table->foreignId('product_sku_id')->constrained();
            $table->foreignId('unit_id')->constrained();

            $table->string('product_name');
            $table->string('hsn_code', 50)->nullable();

            $table->decimal('quantity', 10, 4);
            $table->decimal('unit_price', 15, 4);

            $table->enum('tax_type', ['inclusive', 'exclusive'])->default('exclusive');
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');

            $table->decimal('discount_value', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);

            $table->decimal('taxable_value', 15, 4)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);

            $table->decimal('cgst_amount', 15, 4)->default(0);
            $table->decimal('sgst_amount', 15, 4)->default(0);
            $table->decimal('igst_amount', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);

            $table->decimal('total_amount', 15, 4)->default(0);
            $table->decimal('return_quantity', 10, 4)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
