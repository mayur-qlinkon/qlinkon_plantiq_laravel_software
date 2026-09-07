<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_products', function (Blueprint $table) {
            $table->id();

            // ── Core Relationships ──
            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            // ── Per-Category Display Control ──
            $table->boolean('is_active')->default(true)->index();
            // false = hide from this category only
            // product.show_in_storefront = false = hide from EVERYTHING

            $table->boolean('is_featured')->default(false)->index();
            // Featured products pinned to top of this category

            // ── Per-Category Sort Order ──
            $table->unsignedInteger('sort_order')->default(0);
            // Independent per category — Indoor sort != Outdoor sort

            // ── Audit ──
            $table->foreignId('added_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // ── Constraints ──
            // A product can only appear ONCE per category
            $table->unique(['category_id', 'product_id']);

            // ── Performance Indexes ──
            $table->index(['category_id', 'is_active', 'sort_order']);
            $table->index(['category_id', 'is_featured', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_products');
    }
};
