<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give Challan -> Invoice the same bidirectional link that Order -> Invoice
     * already has. Without it there is no way to tell that a challan has already
     * been billed, which allows the same challan to be converted repeatedly.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('challan_id')
                ->nullable()
                ->after('order_id')
                ->constrained('challans')
                ->nullOnDelete();

            $table->index(['challan_id', 'status']);
        });

        // Backfill: recover the link for invoices already created from a challan,
        // using the line-level trail on challan_items.
        DB::statement("
            UPDATE invoices i
            JOIN invoice_items ii ON ii.invoice_id = i.id
            JOIN challan_items ci ON ci.invoice_item_id = ii.id
            SET i.challan_id = ci.challan_id
            WHERE i.challan_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['challan_id', 'status']);
            $table->dropConstrainedForeignId('challan_id');
        });
    }
};