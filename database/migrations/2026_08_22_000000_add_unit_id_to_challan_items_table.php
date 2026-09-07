<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the missing unit_id snapshot to challan_items.
     *
     * The model's $fillable already declared 'unit_id' but the column was never
     * created, so every read returned null. That broke Challan -> Invoice
     * conversion, because items.*.unit_id is a required field on the invoice form.
     */
    public function up(): void
    {
        Schema::table('challan_items', function (Blueprint $table) {
            $table->foreignId('unit_id')
                ->nullable()
                ->after('unit')
                ->constrained('units')
                ->nullOnDelete();
        });

        // Backfill historical rows from the SKU's unit so existing open challans
        // become convertible without manual re-entry.
        DB::statement("
            UPDATE challan_items ci
            JOIN product_skus ps ON ps.id = ci.product_sku_id
            SET ci.unit_id = ps.unit_id
            WHERE ci.unit_id IS NULL
              AND ps.unit_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('challan_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
        });
    }
};