<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_skus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units');

            $table->string('sku', 100);
            $table->string('barcode', 100)->nullable();

            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('mrp', 10, 2)->nullable();

            $table->string('hsn_code', 20)->nullable();
            $table->decimal('gst_rate', 5, 2)->nullable();

            $table->decimal('order_tax', 5, 2)->default(0);
            $table->enum('tax_type', ['inclusive', 'exclusive'])->default('exclusive');
            $table->integer('stock_alert')->default(0);
            $table->unsignedInteger('total_sold')->default(0);
            $table->index(['product_id', 'total_sold']);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'sku']);
            $table->unique(['company_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_skus');
    }
};
