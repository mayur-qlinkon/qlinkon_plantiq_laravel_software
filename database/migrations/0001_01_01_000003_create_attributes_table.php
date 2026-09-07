<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();

            // 1. THE IRON WALL
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Core Info (Removed the global ->unique() from here)
            $table->string('name', 50)->comment('e.g., Size, Color, Pot Material');
            $table->string('type', 20)->default('text')->comment('UI renderer: text, color, button');

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // 2. Make it unique PER COMPANY
            $table->unique(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
