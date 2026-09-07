<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * return_number was globally unique. Numbers are generated per company
     * (CR-YYYY-0001 restarts at 0001 for every tenant), so the second company
     * to raise a challan return collided on the index and could never create
     * one. The failure was permanent and completely silent.
     *
     * Same shape as 2026_08_21_100000_fix_expense_number_unique_scope.php.
     */
    public function up(): void
    {
        Schema::table('challan_returns', function (Blueprint $table) {
            $table->dropUnique('challan_returns_return_number_unique');
            $table->unique(['company_id', 'return_number'], 'challan_returns_company_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('challan_returns', function (Blueprint $table) {
            $table->dropUnique('challan_returns_company_number_unique');
            $table->unique('return_number');
        });
    }
};