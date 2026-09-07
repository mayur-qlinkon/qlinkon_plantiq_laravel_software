<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {

            $table->id();
            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();            
            
            $table->string('name');
            $table->text('image')->nullable();
            $table->string('slug')->nullable();                      
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            // SaaS-safe uniqueness
            $table->unique(['company_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
