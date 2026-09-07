<?php

// ============================================================
// MIGRATION 1 — purchases
// ============================================================
// Depends on: companies, stores, suppliers, warehouses, users
// Payments handled by your unified payments + payment_allocations
// ============================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();

            // ── OWNERSHIP ──────────────────────────────────────────────
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            // ── REFERENCE NUMBERS ──────────────────────────────────────
            $table->string('purchase_number', 50);
            $table->unique(['company_id', 'purchase_number']);
            // e.g. PO-2024-0001 — generated from your settings counter

            $table->string('supplier_invoice_number', 100)->nullable();
            // Supplier's own bill/invoice number — mandatory for GST input credit claim

            $table->string('supplier_invoice_date')->nullable();
            // Date on supplier's physical bill — can differ from purchase_date

            // ── DATES ──────────────────────────────────────────────────
            $table->date('purchase_date');
            $table->date('due_date')->nullable();
            // Auto-filled as: purchase_date + supplier.credit_days

            // ── STATUS ─────────────────────────────────────────────────
            $table->enum('status', [
                'draft',             // saved, not confirmed
                'ordered',           // confirmed PO sent to supplier
                'partially_received', // some items arrived
                'received',          // all items received → triggers stock
                'cancelled',
            ])->default('draft');

            $table->string('payment_status', 30)->default('unpaid');

            // ── INDIAN GST ─────────────────────────────────────────────
            $table->enum('tax_type', [
                'cgst_sgst',        // intra-state: supplier.state_code == company.state_code
                'igst',             // inter-state: different states
                'none',             // unregistered supplier or exempt
            ])->default('cgst_sgst');
            // Set automatically in backend by comparing supplier.state_code vs company.state_code

            $table->string('supplier_gst_number', 50)->nullable();
            // Snapshot at time of purchase — because supplier can update GST later
            // Critical: if you reference supplier.gst_number directly, amended supplier breaks old bills

            $table->string('company_gst_number', 50)->nullable();
            // Your company's GST — snapshot for the same reason

            // ── AMOUNTS ────────────────────────────────────────────────
            $table->decimal('subtotal', 15, 2)->default(0);
            // Sum of (unit_cost × qty) before any tax/discount

            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('discount_value', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            // Bill-level discount (item-level discount is in purchase_items)

            $table->decimal('taxable_amount', 15, 2)->default(0);
            // subtotal − discount_amount — this is what GST is calculated on

            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            // Split stored separately — needed for GSTR-2 filing report

            $table->decimal('tax_amount', 15, 2)->default(0);
            // cgst + sgst + igst combined — for quick display

            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('other_charges', 15, 2)->default(0);
            // e.g. loading, packaging charges — common in Indian trading

            $table->decimal('round_off', 5, 2)->default(0);
            // -0.50 to +0.49 — every Indian billing software has this. Tally has it.

            $table->decimal('total_amount', 15, 2)->default(0);
            // taxable_amount + tax_amount + shipping + other + round_off

            // ── PAYMENT TRACKING ───────────────────────────────────────
            $table->decimal('paid_amount', 15, 2)->default(0);
            // Synced from payments table: SUM(payments where paymentable = this purchase)

            $table->decimal('balance_amount', 15, 2)->default(0);
            // total_amount − paid_amount

            // ── EXTRA ──────────────────────────────────────────────────
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ── INDEXES ────────────────────────────────────────────────
            $table->index(['company_id', 'store_id']);
            $table->index(['company_id', 'supplier_id']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'payment_status']);
            $table->index(['company_id', 'purchase_date']);
            $table->index(['company_id', 'due_date']);
            // due_date index: needed for "overdue purchases" query — very common
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
