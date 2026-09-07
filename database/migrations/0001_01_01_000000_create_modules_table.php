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
        Schema::create('modules', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->json('depends_on')->nullable();
            $table->text('description')->nullable();
            
            // Price for the add-on. Default 0.
            $table->decimal('price', 10, 2)->default(0);
            
            // is_addon = true means it will show up in the Plan Builder modal for purchase.
            // Default is false so your existing core modules don't accidentally become paid add-ons.
            $table->boolean('is_addon')->default(false);
            
            // Optional: For storing FontAwesome classes (e.g., 'fa-solid fa-users') or image paths
            $table->string('icon')->nullable();
            
            // Helps control the display sequence in the modal
            
            $table->boolean('is_active')->default(true);
            
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
