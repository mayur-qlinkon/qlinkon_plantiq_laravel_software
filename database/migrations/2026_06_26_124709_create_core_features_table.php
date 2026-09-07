<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_features', function (Blueprint $table) {
            $table->id();

            // Display name
            $table->string('name');

            // Optional value shown in summary
            // Example:
            // AI Assistant => Included
            // Storage => 20 GB
            // Backup => Daily
            $table->string('value')->nullable();

            // Optional description
            $table->text('description')->nullable();

            // Font Awesome icon
            $table->string('icon')->default('fa-solid fa-check');

            $table->boolean('is_active')->default(true);

            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_features');
    }
};