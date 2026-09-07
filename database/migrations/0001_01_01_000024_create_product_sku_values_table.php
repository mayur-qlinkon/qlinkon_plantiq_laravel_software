<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot for linking variations (e.g., Red, Large) to a specific SKU
        Schema::create('product_sku_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_sku_id')->constrained('product_skus')->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained('attribute_values')->cascadeOnDelete();
            $table->index(['attribute_id', 'attribute_value_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sku_values');
    }
};
