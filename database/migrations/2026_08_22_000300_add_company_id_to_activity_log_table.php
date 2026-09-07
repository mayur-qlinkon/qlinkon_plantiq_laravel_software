<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give the activity log a tenant column of its own.
     *
     * Spatie's table records only subject and causer, so there is nothing to
     * filter on: the audit screen had to return every company's rows because
     * no column distinguished them. Deriving the company through whereHasMorph
     * does not scale past a single subject type and misses rows written by
     * console commands and webhooks, which have no causer at all.
     */
    public function up(): void
    {
        $table = config('activitylog.table_name');

        Schema::connection(config('activitylog.database_connection'))
            ->table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('company_id')->nullable()->after('id');
                $t->index(['company_id', 'created_at']);
            });

        // Backfill from the causer where one exists. Rows without a causer stay
        // null and are treated as platform-level by the scope below.
        DB::statement("
            UPDATE {$table} a
            JOIN users u ON u.id = a.causer_id
            SET a.company_id = u.company_id
            WHERE a.causer_type = 'App\\\\Models\\\\User'
              AND a.company_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $t) {
                $t->dropIndex(['company_id', 'created_at']);
                $t->dropColumn('company_id');
            });
    }
};