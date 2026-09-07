<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batch_harvest_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('harvest_id')
                ->constrained('production_batch_harvests')
                ->cascadeOnDelete();

            // quantity_edited | cancelled — kept as string (matches HarvestStatus/BatchStatus
            // enum convention elsewhere), validated at service layer via BatchHarvestLogAction enum.
            $table->string('action', 30);

            // Snapshot of quantity_harvested before/after the change.
            // For 'cancelled', old_quantity = the harvested qty, new_quantity = 0.
            $table->unsignedInteger('old_quantity');
            $table->unsignedInteger('new_quantity');

            // Free-text reason — "Quantity corrected after recount", "Worker mistake", etc.
            // Optional so a straightforward cancel doesn't force a reason, but strongly
            // encouraged at the form level for quantity corrections.
            $table->text('remarks')->nullable();

            $table->foreignId('performed_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('performed_at');

            $table->timestamps();

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index(['harvest_id', 'performed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batch_harvest_logs');
    }
};