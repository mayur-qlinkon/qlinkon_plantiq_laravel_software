<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Running total of how much of a purchase line has been returned.
     *
     * Return quantities were validated only as "greater than zero", with
     * nothing comparing them to the original purchase and nothing tracking
     * cumulative returns — so the same line could be returned repeatedly and
     * each pass deducted stock again.
     */
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('quantity_returned', 15, 4)
                ->default(0)
                ->after('quantity_received');
        });

        // Backfill from returns that actually shipped. Draft and cancelled
        // returns never deducted stock, so they must not count here.
        DB::statement("
            UPDATE `purchase_items` AS pi
            SET pi.`quantity_returned` = (
                SELECT COALESCE(SUM(pri.`quantity`), 0)
                FROM `purchase_return_items` AS pri
                INNER JOIN `purchase_returns` AS pr ON pr.`id` = pri.`purchase_return_id`
                WHERE pri.`purchase_item_id` = pi.`id`
                  AND pr.`status` = 'returned'
                  AND pr.`deleted_at` IS NULL
            )
        ");
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('quantity_returned');
        });
    }
};