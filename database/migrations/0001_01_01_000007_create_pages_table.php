<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pages')) {
            return;
        }

        Schema::create('pages', function (Blueprint $table) {
            $table->id();

            // Multi-tenancy
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Core Content
            $table->string('title');
            $table->string('slug');
            $table->longText('content')->nullable(); // LONGTEXT for heavy HTML/Base64 images

            // Categorization
            // Using string instead of DB enum for future scalability
            $table->string('type', 50)->default('custom')->index();

            // SEO Metadata
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();

            // Visibility
            $table->boolean('is_published')->default(false)->index();

            // Audit Trails
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes(); // Safenet against accidental deletion

            // 🌟 CRITICAL: A slug must be unique ONLY within the specific company
            $table->unique(['company_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
