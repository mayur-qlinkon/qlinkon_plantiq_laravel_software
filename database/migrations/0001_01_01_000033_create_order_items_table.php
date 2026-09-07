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
            Schema::create('order_items', function (Blueprint $table) {

                $table->id();

                $table->foreignId('order_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('product_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                $table->foreignId('sku_id')
                    ->nullable()
                    ->constrained('product_skus')
                    ->nullOnDelete();

                // ── Snapshot — never changes after order placed ──
                $table->string('product_name', 255);
                $table->string('sku_label', 100)->nullable();
                $table->string('sku_code', 50)->nullable();
                $table->string('product_image', 500)->nullable();
                $table->string('hsn_code', 20)->nullable();

                // ── Pricing snapshot ──
                $table->decimal('unit_price', 10, 2);
                $table->decimal('cost_price', 10, 2)->default(0);
                $table->unsignedSmallInteger('qty');
                $table->decimal('discount_amount', 10, 2)->default(0);

                // ── GST per line item ──
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('cgst_rate', 5, 2)->default(0);
                $table->decimal('sgst_rate', 5, 2)->default(0);
                $table->decimal('igst_rate', 5, 2)->default(0);
                $table->decimal('cgst_amount', 10, 2)->default(0);
                $table->decimal('sgst_amount', 10, 2)->default(0);
                $table->decimal('igst_amount', 10, 2)->default(0);
                $table->decimal('tax_amount', 10, 2)->default(0);

                $table->decimal('line_total', 10, 2);

                $table->enum('status', [
                    'pending',
                    'confirmed',
                    'shipped',
                    'delivered',
                    'cancelled',
                    'returned',
                ])->default('pending');

                $table->text('notes')->nullable();

                $table->timestamps();

                $table->index(['order_id']);
                $table->index(['product_id']);
                $table->index(['sku_id']);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
