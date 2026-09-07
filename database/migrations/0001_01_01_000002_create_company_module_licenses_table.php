<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_module_licenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('module_id')
                ->constrained()
                ->cascadeOnDelete();

            // NULL = Unlimited Seats
            $table->unsignedInteger('seat_limit')->nullable();            

            $table->boolean('is_active')->default(true);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'module_id']);

            $table->index('company_id');
            $table->index('module_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_module_licenses');
    }
};