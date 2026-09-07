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
        Schema::create('production_sites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            // Optional, non-structural soft-link to a Store for reporting
            // only. Production's spatial hierarchy is deliberately
            // independent of Warehouse/Store.
            $table->foreignId('store_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_sites');
    }
};