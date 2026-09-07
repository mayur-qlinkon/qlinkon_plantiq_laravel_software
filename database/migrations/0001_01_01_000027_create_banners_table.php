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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();

            // 🔐 SaaS Isolation
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // 🧩 TYPE SYSTEM (what kind of banner)
            $table->enum('type', [
                'hero',        // main homepage slider
                'promo',       // offer banners
                'ad',          // advertisement
                'category',    // category highlight
                'popup',        // future: popup banners
            ])->default('hero')->index();

            // 📍 WHERE TO SHOW
            $table->string('position')->default('home_top')->index();
            // examples:
            // home_top, home_middle, home_bottom
            // category_page, product_page

            // 🖼️ CONTENT
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('admin_label', 150)->nullable();
            $table->string('image'); // main banner image
            $table->string('mobile_image')->nullable(); // mobile optimized image

            // 🔗 ACTION
            $table->string('link')->nullable(); // where to redirect
            $table->string('button_text')->nullable();
            // Add these columns
            $table->string('alt_text')->nullable();
            // ↑ SEO + accessibility — Google penalizes images without alt text

            $table->unsignedInteger('click_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            // ↑ Basic analytics — which banner gets most clicks?
            // Without this you're blind on what's performing

            $table->string('target')->default('_self');
            // ↑ _self or _blank — some promo links open external sites

            // 🎯 TARGETING (VERY POWERFUL)
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            // ⚙️ DISPLAY CONTROL
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0);

            // ⏳ SCHEDULING (VERY IMPORTANT)
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            // 📊 FUTURE EXTENSIONS
            $table->json('meta')->nullable();
            // example:
            // {
            //   "bg_color": "#f5f5f5",
            //   "text_color": "#000",
            //   "animation": "fade"
            // }

            // 🧾 AUDIT
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // ⚡ PERFORMANCE INDEXES
            $table->index(['company_id', 'type', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
