<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batch_losses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('plant_batch_id')
                ->constrained('production_plant_batches')
                ->cascadeOnDelete();

            // always > 0 — enforced at service layer
            $table->unsignedInteger('quantity_lost');

            $table->date('loss_date');

            // mortality | disease | pest | weather | mechanical | theft | other
            $table->string('reason');

            $table->foreignId('recorded_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index(['plant_batch_id', 'loss_date']);
            $table->index(['company_id', 'loss_date']);
            $table->index(['company_id', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batch_losses');
    }
};