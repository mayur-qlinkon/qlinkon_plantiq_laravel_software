<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * supplier_invoice_date was declared as string() while purchase_date and
     * due_date beside it are proper dates, so range filters and sorting on it
     * compared text instead of dates.
     */
    public function up(): void
    {
        // Anything that will not parse becomes NULL rather than failing the
        // ALTER or landing as a zero date under strict mode.
        DB::statement("
            UPDATE `purchases`
            SET `supplier_invoice_date` = NULL
            WHERE `supplier_invoice_date` IS NOT NULL
              AND STR_TO_DATE(`supplier_invoice_date`, '%Y-%m-%d') IS NULL
        ");

        DB::statement('ALTER TABLE `purchases` MODIFY `supplier_invoice_date` DATE NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `purchases` MODIFY `supplier_invoice_date` VARCHAR(255) NULL');
    }
};