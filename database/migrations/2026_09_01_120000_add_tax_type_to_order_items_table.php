<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // order_items is a pricing snapshot, and whether a line's price already
        // contained its GST is part of that price. Returns and reprints have to
        // reproduce the original arithmetic, and they cannot do that from the
        // SKU because the SKU may have been switched between inclusive and
        // exclusive since the order was placed.
        Schema::table('order_items', function (Blueprint $table) {
            $table->enum('tax_type', ['inclusive', 'exclusive'])
                ->default('exclusive')
                ->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('tax_type');
        });
    }
};