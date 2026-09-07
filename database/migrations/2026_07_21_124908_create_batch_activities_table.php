<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batch_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('plant_batch_id')
                ->constrained('production_plant_batches')
                ->cascadeOnDelete();

            // watering | fertilizer | pest_control | pruning | inspection | sorting | other
            $table->string('activity_type');

            $table->datetime('performed_on');

            $table->foreignId('performed_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index(['plant_batch_id', 'performed_on']);
            $table->index(['company_id', 'performed_on']);
            $table->index(['company_id', 'activity_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batch_activities');
    }
};