<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * PurchaseService wrote taxable_value and total_amount — the invoice_items
     * column names — so purchase_items.taxable_amount and total_price were
     * silently dropped by the fillable filter and left at 0 on every row.
     *
     * subtotal, discount_amount and tax_amount were all stored correctly, so
     * the two missing values can be reconstructed exactly:
     *
     *   afterDiscount = GREATEST(0, subtotal - discount_amount)
     *   exclusive: taxable = afterDiscount
     *   inclusive: taxable = afterDiscount - tax_amount
     *   total_price = taxable + tax_amount
     */
    public function up(): void
    {
        DB::statement("
            UPDATE `purchase_items`
            SET
                `taxable_amount` = GREATEST(0, `subtotal` - `discount_amount`)
                                   - CASE WHEN `tax_type` = 'inclusive' THEN `tax_amount` ELSE 0 END,
                `total_price`    = GREATEST(0, `subtotal` - `discount_amount`)
                                   + CASE WHEN `tax_type` = 'inclusive' THEN 0 ELSE `tax_amount` END
            WHERE `taxable_amount` = 0
              AND `total_price` = 0
              AND `subtotal` > 0
        ");
    }

    public function down(): void
    {
        // Restoring zeros would be destructive; the recomputed values are correct.
    }
};