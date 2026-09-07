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
        Schema::create('challan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challan_id')->constrained('challans')->cascadeOnDelete();

            // ── Product Reference ──
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_sku_id')->nullable()->constrained('product_skus')->nullOnDelete();

            // ── Snapshot (print old challans correctly even if product changes) ──
            $table->string('product_name');
            $table->string('sku_label')->nullable();   // Red / XL
            $table->string('sku_code')->nullable();    // SKU-001
            $table->string('hsn_code', 20)->nullable();
            $table->string('unit')->nullable();        // pcs, kg, mtr, box

            // ── Quantities (the 4-state tracker) ──
            $table->decimal('qty_sent', 10, 2)->default(0);
            $table->decimal('qty_returned', 10, 2)->default(0);
            $table->decimal('qty_invoiced', 10, 2)->default(0);
            // qty_pending = qty_sent - qty_returned - qty_invoiced (computed, not stored)

            // ── Pricing (indicative) ──
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('line_value', 12, 2)->default(0); // unit_price * qty_sent

            // ── Conversion Reference ──
            // When this line gets invoiced, track which invoice item it maps to
            $table->foreignId('invoice_item_id')->nullable()
                ->constrained('invoice_items')->nullOnDelete();
            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('product_batches')
                ->nullOnDelete();
            $table->string('batch_number', 100)->nullable();
            $table->date('expiry_date')->nullable();

            $table->text('notes')->nullable(); // "2 pcs damaged on arrival"

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challan_items');
    }
};
