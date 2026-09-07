<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_article_feedback', function (Blueprint $table) {
            $table->id();

            $table->foreignId('help_article_id')
                ->constrained('help_articles')
                ->cascadeOnDelete();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // true = helpful, false = not helpful
            $table->boolean('is_helpful');

            $table->ipAddress('ip_address')->nullable();

            $table->timestamps();

            // Optional performance indexes
            $table->index('help_article_id');
            $table->index('is_helpful');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_article_feedback');
    }
};