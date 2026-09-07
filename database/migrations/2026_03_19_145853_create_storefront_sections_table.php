<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_sections', function (Blueprint $table) {
            $table->id();

            // ── SaaS Isolation ──
            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();

            // ── Identity ──
            $table->string('title')->nullable();
            // Display name shown to admin: "Indoor Plants", "Best Sellers", "New Arrivals"

            $table->string('subtitle')->nullable();
            $table->string('admin_label', 150)->nullable();
            // Optional description shown below section heading on storefront

            // ── Section Type ──
            $table->enum('type', [
                'category',      // pulls products from a specific category
                'featured',      // pulls products marked is_featured globally
                'new_arrivals',  // pulls latest products by created_at
                'best_sellers',  // pulls most sold products (future)
                'manual',        // hand-picked product list (future: section_products pivot)
                'banner',        // links to banners table
                'custom_html',   // raw HTML block (for announcements, promos)
            ])->default('category')->index();

            // ── Type-specific Reference ──
            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('banner_position', 50)
                ->nullable();
            // Only used when type = 'category'

            // ── Display Config ──
            $table->string('layout')->default('grid');
            // grid | list | carousel | horizontal_scroll
            // Controls how products render in this section

            $table->unsignedTinyInteger('products_limit')->default(8);
            // How many products to show: 4, 8, 12, 16

            $table->unsignedTinyInteger('columns')->default(4);
            // Grid columns: 2, 3, 4, 5, 6

            $table->boolean('show_view_all')->default(true);
            // Show "View All →" link at section header

            $table->string('view_all_url')->nullable();
            // Custom URL for View All — defaults to category URL if type=category

            // ── Visual Config ──
            $table->string('bg_color')->nullable();
            // Section background color: null = default page bg

            $table->string('heading_color')->nullable();
            // Override heading text color

            $table->boolean('show_section_title')->default(true);
            // Can hide title for clean banner-only sections

            // ── Content (for custom_html type) ──
            $table->text('custom_html')->nullable();

            // ── Display Control ──
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            // Controls homepage section order — this is the GLOBAL order

            // ── Scheduling (same pattern as banners) ──
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            // Festival sales, seasonal sections

            // ── Device Visibility ──
            $table->boolean('show_on_mobile')->default(true);
            $table->boolean('show_on_desktop')->default(true);
            // Hide heavy sections on mobile for performance

            // ── Analytics ──
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('click_count')->default(0);

            // ── Audit ──
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // ── Performance Indexes ──
            $table->index(['company_id', 'is_active', 'sort_order']);
            $table->index(['company_id', 'type', 'is_active']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_sections');
    }
};
