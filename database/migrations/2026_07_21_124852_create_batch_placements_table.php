<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batch_placements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('plant_batch_id')
                ->constrained('production_plant_batches')
                ->cascadeOnDelete();

            $table->foreignId('growing_space_id')
                ->constrained('production_growing_spaces')
                ->restrictOnDelete();

            $table->dateTime('placed_at');
            $table->dateTime('ended_at')->nullable();

            $table->foreignId('placed_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index(['plant_batch_id', 'ended_at']);   // "current placement" queries
            $table->index(['growing_space_id', 'ended_at']); // "what is in this space now"
            $table->index(['company_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batch_placements');
    }
};