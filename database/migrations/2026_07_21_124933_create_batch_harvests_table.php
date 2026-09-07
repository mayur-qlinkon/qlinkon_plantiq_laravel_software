<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batch_harvests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('plant_batch_id')
                ->constrained('production_plant_batches')
                ->cascadeOnDelete();

            // always > 0 — enforced at service layer
            $table->unsignedInteger('quantity_harvested');

             $table->string('status', 20)->default('pending');
 
            // Warehouse-accepted quantity — set only on approval, may differ
            // from quantity_harvested (the worker-reported figure, immutable).
            $table->unsignedInteger('received_quantity')->nullable();                                        

            $table->foreignId('harvested_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('harvested_on');

             $table->foreignId('received_by')
                ->nullable()                
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('received_at')->nullable();    

            // Soft reference only — no FK constraint (warehouse may not always exist; V2 stock integration)
            $table->unsignedBigInteger('warehouse_id')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index(['plant_batch_id', 'harvested_on']);
            $table->index(['company_id', 'harvested_on']);
            $table->index(['company_id', 'warehouse_id']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batch_harvests');
    }
};