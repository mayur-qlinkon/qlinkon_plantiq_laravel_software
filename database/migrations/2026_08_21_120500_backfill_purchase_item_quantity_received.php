<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * syncLineItems() did not set quantity_received until a later fix, so
     * purchases updated before it can sit at status 'received' with 0 received
     * on every line. Anything reading the column believes nothing arrived —
     * PlantBatchService seeds a plant batch's initial_quantity from it, and the
     * return cap is about to rely on it too.
     *
     * A fully received purchase means every line arrived in full, which is the
     * only rule the code has ever applied.
     */
    public function up(): void
    {
        DB::statement("
            UPDATE `purchase_items` AS pi
            INNER JOIN `purchases` AS p ON p.`id` = pi.`purchase_id`
            SET pi.`quantity_received` = pi.`quantity`
            WHERE p.`status` = 'received'
              AND pi.`quantity_received` = 0
              AND pi.`quantity` > 0
        ");
    }

    public function down(): void
    {
        // Restoring zeros would reintroduce the bug.
    }
};