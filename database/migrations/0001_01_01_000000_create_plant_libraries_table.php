<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plant_library', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            // Suggested label only — NOT a foreign key.
            // Categories are tenant-scoped (Tenantable), so this is resolved
            // per-tenant at import time (created if missing, editable after).
            $table->string('category_name')->nullable();

            $table->enum('type', ['single', 'variable'])->default('single');
            $table->enum('product_type', ['sellable', 'catalog'])->default('sellable');

            // Suggested label only — NOT a foreign key.
            // Units are tenant-scoped, resolved per-tenant at import time.
            $table->string('unit_short_name')->nullable();

            $table->text('description')->nullable();

            // [{ "title": "...", "description": "..." }, ...] — same shape
            // ProductWithSkuImporter builds from title1/value1 … title16/value16.
            $table->json('product_guide')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_library');
    }
};