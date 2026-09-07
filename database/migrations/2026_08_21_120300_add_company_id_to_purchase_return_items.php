<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * purchase_return_items carried no company_id, so tenant isolation for
     * return lines rested entirely on reaching them through their parent.
     * Every sibling line-item table (purchase_items, invoice_items) carries
     * its own company_id; this brings returns in line.
     */
    public function up(): void
    {
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('purchase_return_id');
        });

        DB::statement("
            UPDATE `purchase_return_items` AS pri
            INNER JOIN `purchase_returns` AS pr ON pr.`id` = pri.`purchase_return_id`
            SET pri.`company_id` = pr.`company_id`
        ");

        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->index(['company_id', 'product_sku_id']);
        });
    }

    public function down(): void
    {
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'product_sku_id']);
            $table->dropColumn('company_id');
        });
    }
};