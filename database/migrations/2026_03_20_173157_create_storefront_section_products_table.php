<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_section_products', function (Blueprint $table) {

            $table->id();

            $table->foreignId('storefront_section_id')
                ->constrained('storefront_sections')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // ── Display order within this section ──
            $table->unsignedSmallInteger('sort_order')->default(0);

            // ── Who added this product to the section ──
            $table->foreignId('added_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // ── One product per section only ──
            $table->unique(['storefront_section_id', 'product_id'], 'ssp_section_product_unique');

            $table->index(['storefront_section_id', 'sort_order'], 'ssp_section_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_section_products');
    }
};
