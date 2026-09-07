<?php

// ============================================================
// MIGRATION 2 — purchase_items
// ============================================================
// Depends on: purchases, products, product_skus, units
// This is where stock ops connect — product_sku_id is critical
// ============================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();

            // ── OWNERSHIP ──────────────────────────────────────────────
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // company_id here = faster queries without joining purchases

            // ── PRODUCT REFERENCES ─────────────────────────────────────
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_sku_id')->constrained('product_skus')->restrictOnDelete();
            // BOTH are needed:
            // product_id    → for product-level reports ("total spent on Cement this year")
            // product_sku_id → for ALL stock operations (product_stocks, stock_movements)
            // Never use just product_id for stock — your schema runs on SKUs

            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            // purchase_unit_id from product — could differ from sale unit

            // ── GST / HSN ──────────────────────────────────────────────
            $table->string('hsn_code', 20)->nullable();
            // Snapshot from product.hsn_code at time of purchase
            // HSN mandatory on GST invoice above ₹5L turnover
            // Stored as snapshot because product HSN can be updated later

            $table->decimal('tax_percent', 5, 2)->default(0);
            // Total GST % — e.g. 18 for 18% GST
            // Snapshot from product_sku.order_tax at time of purchase

            $table->enum('tax_type', ['inclusive', 'exclusive'])->default('exclusive');
            // Snapshot from product_sku.tax_type
            // inclusive: price already has tax — extract it
            // exclusive: price + tax on top

            // These are calculated from purchase.tax_type (cgst_sgst or igst)
            // and item's tax_percent — stored for line-level GST report
            $table->decimal('cgst_percent', 5, 2)->default(0);   // tax_percent / 2
            $table->decimal('sgst_percent', 5, 2)->default(0);   // tax_percent / 2
            $table->decimal('igst_percent', 5, 2)->default(0);   // full tax_percent

            // ── QUANTITIES ─────────────────────────────────────────────
            $table->decimal('quantity', 15, 4);
            // Total quantity ordered

            $table->decimal('quantity_received', 15, 4)->default(0);
            // Updated when goods arrive — enables partial receive
            // When quantity_received == quantity → item fully received
            // When quantity_received < quantity  → item partially received
            // This drives purchases.status auto-update

            // ── PRICING ────────────────────────────────────────────────
            $table->decimal('unit_cost', 15, 4);
            // What you're paying per unit to supplier
            // IMPORTANT: this becomes the new product_sku.cost on receive
            // so your selling margin stays accurate

            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('discount_value', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            // Item-level trade discount from supplier — common in Indian wholesale

            $table->decimal('subtotal', 15, 4)->default(0);
            // unit_cost × quantity

            $table->decimal('taxable_amount', 15, 4)->default(0);
            // subtotal − discount_amount

            $table->decimal('cgst_amount', 15, 4)->default(0);
            $table->decimal('sgst_amount', 15, 4)->default(0);
            $table->decimal('igst_amount', 15, 4)->default(0);

            $table->decimal('tax_amount', 15, 4)->default(0);
            // Total tax on this line item

            $table->decimal('total_price', 15, 4)->default(0);
            // taxable_amount + tax_amount (the final line total)

            // ── BATCH TRACKING ─────────────────────────────────────────
            $table->string('batch_number', 100)->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            // When status → received:
            // 1. Create product_batches record from these fields
            // 2. Link stock_movements.batch_id to that new record

            // ── EXTRA ──────────────────────────────────────────────────
            $table->text('notes')->nullable();

            $table->timestamps();

            // ── INDEXES ────────────────────────────────────────────────
            $table->index(['purchase_id', 'product_sku_id']);
            $table->index(['company_id', 'product_id']);
            $table->index(['company_id', 'product_sku_id']);
            // Fast query: "all purchases of this SKU" for price history
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
