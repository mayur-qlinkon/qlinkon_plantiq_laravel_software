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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users'); // Who incurred the expense
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();

            $table->string('expense_number')->unique();
            // ── Merchant & Tax Details (Crucial for Indian ITC) ──
            $table->string('currency_code', 3)->default('INR');
            $table->decimal('exchange_rate', 10, 4)->default(1);
            $table->enum('tax_type', ['cgst_sgst', 'igst', 'none'])->default('cgst_sgst');
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->string('merchant_name');
            $table->string('merchant_gstin', 15)->nullable(); // To claim GST credit
            $table->string('reference_number')->nullable(); // Invoice or Bill number
            $table->date('expense_date');

            // ── The Financials ──
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->decimal('round_off', 8, 2)->default(0);
            $table->decimal('base_amount', 15, 2);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2); // Base + Taxes

            // ── Workflow & Attributes ──
            $table->boolean('is_reimbursable')->default(false); // Did the employee pay out of pocket?
            $table->boolean('is_billable')->default(false); // Can we bill this to a client later?

            // Status: draft, pending_approval, approved, rejected, reimbursed
            $table->string('status', 30)->default('pending_approval');
            $table->string('attachment')->nullable(); // bill image/pdf
            $table->enum('source', ['manual', 'pos', 'api', 'import'])->default('manual');
            $table->text('notes')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'expense_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
