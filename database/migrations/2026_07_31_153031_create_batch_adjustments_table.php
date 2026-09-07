<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batch_adjustments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('plant_batch_id')
                ->constrained('production_plant_batches')
                ->cascadeOnDelete();

            // Snapshot of current_quantity before/after this manual correction.
            // initial_quantity is NEVER touched here — stays permanent, same
            // convention as Loss and Harvest.
            $table->unsignedInteger('old_quantity');
            $table->unsignedInteger('new_quantity');

            // Signed — new_quantity - old_quantity. Stored so the UI can show
            // a quick +/- without recalculating from old/new every time.
            $table->integer('delta');

            // Free-text reason for the correction — "Physical recount",
            // "Damaged stock found", etc. Encouraged (not enforced) at the form level.
            $table->text('notes')->nullable();

            $table->foreignId('adjusted_by')
                ->constrained('users')
                ->restrictOnDelete();

            // created_at only — insert-only ledger, rows are never edited.
            $table->timestamp('created_at')->nullable();

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index(['plant_batch_id', 'created_at']);
            $table->index(['company_id', 'created_at']);
        });
        
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batch_adjustments');
    }
};