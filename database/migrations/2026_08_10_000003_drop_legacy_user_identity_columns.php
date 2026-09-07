<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Refuse to drop the legacy flags while any row still carries a
        // pre-migration value. Once these columns are gone, identity cannot
        // be reconstructed from anything else in the database.
        $stale = DB::table('users')
            ->whereIn('user_type', ['full', 'employee'])
            ->count();

        if ($stale > 0) {
            throw new RuntimeException(
                "Aborting: {$stale} users still hold a legacy user_type. "
                ."Run the backfill migration before dropping the flag columns."
            );
        }

        $legacyColumns = [
            'is_super_admin',
            'is_company_admin',
            'is_customer',
            'is_employee',
            'phone_number',
        ];

        foreach ($legacyColumns as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        // Fresh installs already carry this index from the create migration.
        if (! Schema::hasIndex('users', 'users_user_type_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('user_type');
            });
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: the dropped flags held data that cannot
        // be regenerated. Restore from the pre-migration database backup.
    }
};