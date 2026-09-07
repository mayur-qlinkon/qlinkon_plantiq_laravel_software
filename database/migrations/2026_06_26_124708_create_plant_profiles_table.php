<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plant_profiles', function (Blueprint $table) {
            $table->id();
            
            // e.g., "50 Plants", "100 Plants"
            $table->string('name'); 
            $table->string('image')->nullable();
            // For backend validation (e.g., limit tenant to exactly 50 entries)
            $table->unsignedInteger('plant_limit')->default(0);
            
            // Base price for this tier (e.g., 1000.00)
            $table->decimal('price', 10, 2)->default(0);
            
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_profiles');
    }
};