<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_kits', function (Blueprint $table) {
            $table->id();
            
            // e.g., "QR Fiber Tag"
            $table->string('name');
            $table->string('image')->nullable();
            
            // e.g., "with waterproof Printed Kit"
            $table->text('description')->nullable();
            
            // Kit price per unit/profile (e.g., 100.00)
            $table->decimal('price', 10, 2)->default(0);
            
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_kits');
    }
};