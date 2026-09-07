<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();

            // 🔐 SaaS Isolation
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();

            // 👤 Customer Relations & Snapshots (Future-Safe)
            $table->foreignId('customer_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('customer_name')->nullable(); // Walk-in or Snapshotted name
            $table->string('customer_phone', 20)->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_gstin', 50)->nullable();

            // 📍 Address Snapshots
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();

            // 📄 References
            $table->string('quotation_number', 50);
            $table->string('reference_number', 100)->nullable(); // e.g., Customer PO or RFQ number

            // 📅 Dates
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();

            // 🔄 Conversion Link
            $table->foreignId('converted_to_invoice_id')
                ->nullable()
                ->constrained('invoices')
                ->nullOnDelete();
            $table->dateTime('converted_at')->nullable();

            // 📊 Status
            $table->enum('status', [
                'draft',
                'sent',
                'accepted',
                'rejected',
                'expired',
                'converted',
            ])->default('draft')->index();

            // 🌍 Currency & Exchange (Matching your Invoice schema)
            $table->string('currency_code', 3)->default('INR');
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);

            // 🇮🇳 GST Context
            $table->string('supply_state', 100)->nullable();
            $table->enum('gst_treatment', [
                'registered',
                'unregistered',
                'composition',
                'overseas',
                'sez',
            ])->default('unregistered');

            // 💰 Financials
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
            $table->decimal('grand_total', 15, 2)->default(0);

            // 📝 Notes
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // 🔒 Unique Indexes
            $table->unique(['company_id', 'quotation_number']);
            $table->index(['company_id', 'quotation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
