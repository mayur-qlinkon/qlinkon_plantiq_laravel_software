<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Help Categories Table
        |--------------------------------------------------------------------------
        */
        Schema::create('help_categories', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('slug')->unique();

            // Lucide icon name
            $table->string('icon')->nullable();

            $table->integer('sort_order')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Help Articles Table
        |--------------------------------------------------------------------------
        */
        Schema::create('help_articles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('help_category_id')
                ->constrained('help_categories')
                ->cascadeOnDelete();

            $table->string('title');

            $table->string('slug')->unique();

            // Plain/simple HTML content
            $table->text('content');

            // Optional YouTube video URL
            $table->string('video_url')->nullable();

            $table->integer('sort_order')->default(0);

            $table->boolean('is_published')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_articles');
        Schema::dropIfExists('help_categories');
    }
};