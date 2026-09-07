<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Batches created while StorePlantBatchRequest still validated the old
     * 'batch_date' key never received a batch_start_datetime, because the
     * renamed key never reached mass assignment. Fall back to created_at,
     * which is the closest available record of when the batch was entered.
     */
    public function up(): void
    {
        DB::table('production_plant_batches')
            ->whereNull('batch_start_datetime')
            ->update(['batch_start_datetime' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        // Intentionally irreversible — the original NULLs carried no information.
    }
};