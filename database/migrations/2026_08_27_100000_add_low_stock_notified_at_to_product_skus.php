<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_skus', function (Blueprint $table) {
            // Set when the daily low-stock job has already announced this SKU,
            // and cleared again once stock climbs back above stock_alert.
            //
            // NULL means "not currently flagged". That is the whole state
            // machine: it makes the job report a SKU once per dip instead of
            // every night until somebody restocks it.
            //
            // Existing rows start as NULL, so the first run after deploy will
            // announce everything currently below its threshold. That is
            // intended — it is the tenant's opening action list.
            $table->timestamp('low_stock_notified_at')->nullable()->after('stock_alert');
        });
    }

    public function down(): void
    {
        Schema::table('product_skus', function (Blueprint $table) {
            $table->dropColumn('low_stock_notified_at');
        });
    }
};