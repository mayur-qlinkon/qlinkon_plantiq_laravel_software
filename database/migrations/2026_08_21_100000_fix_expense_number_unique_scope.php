<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * expense_number was globally unique. Numbers are generated per company
     * (EXP-YYYYMM-0001 restarts at 0001 for every tenant), so the second
     * company to log an expense in any given month collided on the index and
     * could never create one — the failure was permanent and silent.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique('expenses_expense_number_unique');
            $table->unique(['company_id', 'expense_number'], 'expenses_company_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique('expenses_company_number_unique');
            $table->unique('expense_number');
        });
    }
};