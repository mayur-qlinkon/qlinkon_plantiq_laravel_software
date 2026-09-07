<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plant_library_media', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plant_library_id')
                ->constrained('plant_library')
                ->cascadeOnDelete();

            // Mirrors product_media.media_type exactly — image or youtube.
            $table->enum('media_type', ['image', 'youtube'])->default('image');

            // Local storage path (e.g. plant-library/img.jpg) OR the YouTube URL.
            $table->string('media_path');

            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_library_media');
    }
};