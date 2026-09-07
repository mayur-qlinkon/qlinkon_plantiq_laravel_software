<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();

            // Optional but highly recommended for SaaS: Widen the Iron Wall to child tables
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();

            $table->string('value', 100)->comment('e.g., Red, Small, Ceramic');
            $table->string('color_code', 20)->nullable()->comment('Hex code like #FF0000');

            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['attribute_id', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
