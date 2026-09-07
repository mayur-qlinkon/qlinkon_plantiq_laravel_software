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
        Schema::create('product_media', function (Blueprint $table) {
            $table->id();

            // 🛡️ 1. The Iron Wall (For Tenantable trait security)
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();            

            // 2. Parent Link
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // 🌟 3. PRO FEATURE: Optional link to a specific variation (SKU)
            $table->foreignId('product_sku_id')
                ->nullable()
                ->constrained('product_skus')
                ->cascadeOnDelete()
                ->comment('If set, this image belongs to a specific variation (e.g., the Red shirt)');

            $table->enum('media_type', ['image', 'youtube'])->default('image');

            // Holds the local path (e.g., products/img.jpg) OR the YouTube URL/ID
            $table->string('media_path')->comment('File path or YouTube URL');

            $table->boolean('is_primary')->default(false)->comment('Only images should be primary');
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_media');
    }
};
