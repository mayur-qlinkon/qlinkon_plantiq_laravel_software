<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purchase soft-deletes but PurchaseItem did not, so destroy() hard-deleted
     * the lines while keeping the header recoverable — restoring a purchase
     * returned an empty order. generatePurchaseNumber() reads withTrashed(),
     * confirming trashed purchases are meant to survive.
     */
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};