<?php

// ============================================================
// MIGRATION 3 — purchase_returns + purchase_return_items
// ============================================================
// Depends on: purchases, purchase_items, product_skus, units
// Kept intentionally simple — mirrors purchases structure
// ============================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();

            // ── OWNERSHIP ──────────────────────────────────────────────
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_id')->constrained()->restrictOnDelete();
            // Must link to original purchase — can't return what wasn't purchased
            $table->foreignId('created_by')->constrained('users');

            // ── REFERENCE ──────────────────────────────────────────────
            $table->string('return_number', 50);
            $table->unique(['company_id', 'return_number']);
            // e.g. PR-RTN-2024-0001

            $table->string('supplier_credit_note_number', 100)->nullable();
            // Supplier issues a credit note against your return
            // This number goes in your books as debit note reference

            // ── DATES ──────────────────────────────────────────────────
            $table->date('return_date');

            // ── STATUS ─────────────────────────────────────────────────
            $table->enum('status', [
                'draft',
                'returned',    // goods sent back to supplier → triggers stock deduction
                'cancelled',
            ])->default('draft');

            $table->enum('payment_status', [
                'pending',       // waiting for supplier to refund/credit
                'adjusted',      // adjusted against a future purchase bill
                'refunded',      // supplier returned the money
            ])->default('pending');

            // ── INDIAN GST ─────────────────────────────────────────────
            $table->enum('tax_type', ['cgst_sgst', 'igst', 'none'])->default('cgst_sgst');
            // Mirror from original purchase

            // ── AMOUNTS ────────────────────────────────────────────────
            $table->decimal('subtotal', 15, 2)->default(0);
            
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('discount_value', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);

            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);

            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'supplier_id']);
            $table->index(['company_id', 'purchase_id']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_item_id')->constrained()->restrictOnDelete();
            // Must link to original purchase line — validates you can only return what was received
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_sku_id')->constrained('product_skus')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();

            $table->string('hsn_code', 20)->nullable();  // snapshot

            $table->decimal('quantity', 15, 4);
            // Cannot exceed purchase_item.quantity_received — validate in backend

            $table->decimal('unit_cost', 15, 4);         // snapshot from original purchase_item

            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('cgst_percent', 5, 2)->default(0);
            $table->decimal('sgst_percent', 5, 2)->default(0);
            $table->decimal('igst_percent', 5, 2)->default(0);

            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('discount_value', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);

            $table->decimal('taxable_amount', 15, 4)->default(0);
            $table->decimal('cgst_amount', 15, 4)->default(0);
            $table->decimal('sgst_amount', 15, 4)->default(0);
            $table->decimal('igst_amount', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('total_price', 15, 4)->default(0);

            // Batch info (which batch is being returned)
            $table->string('batch_number', 100)->nullable();

            $table->enum('return_reason', [
                'damaged',
                'wrong_item',
                'excess_quantity',
                'quality_issue',
                'expired',
                'other',
            ])->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['purchase_return_id', 'product_sku_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
    }
};
