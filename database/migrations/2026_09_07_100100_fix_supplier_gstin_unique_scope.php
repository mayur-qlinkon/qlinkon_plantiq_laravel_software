<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * gstin was globally unique. Two tenants buying from the same
     * manufacturer legitimately hold the same GSTIN, so the second tenant to
     * record that supplier hit an unhandled 500 and could never add it.
     *
     * deleted_at is deliberately NOT part of this index. MySQL excludes any
     * row with a NULL column from a unique index, and deleted_at is NULL on
     * every live row, which would disable the constraint entirely. The
     * validation rules are written to match this index exactly, so a
     * soft-deleted supplier still reserves its GSTIN within its own company
     * and the user sees a field error instead of a crash.
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropUnique('suppliers_gstin_unique');
            $table->unique(['company_id', 'gstin'], 'suppliers_company_gstin_unique');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropUnique('suppliers_company_gstin_unique');
            $table->unique('gstin');
        });
    }
};