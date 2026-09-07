<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('employees', 'reporting_to')) {
            return;
        }

        // Preserve the original users.id value. This makes the remap auditable,
        // recoverable, and safe to re-run — the source of truth never changes.
        if (! Schema::hasColumn('employees', 'reporting_to_legacy_user_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->unsignedBigInteger('reporting_to_legacy_user_id')
                    ->nullable()
                    ->after('reporting_to');
            });

            DB::statement('UPDATE employees SET reporting_to_legacy_user_id = reporting_to');
        }

        try {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['reporting_to']);
            });
        } catch (\Throwable $e) {
            // Foreign key may not exist on a fresh install.
        }

        // Read from the preserved column, never from reporting_to itself, so
        // running this twice produces the same result instead of corrupting it.
        DB::statement('
            UPDATE employees e
            LEFT JOIN employees manager
                ON manager.user_id = e.reporting_to_legacy_user_id
            SET e.reporting_to = manager.id
            WHERE e.reporting_to_legacy_user_id IS NOT NULL
        ');

        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('reporting_to')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('employees', 'reporting_to_legacy_user_id')) {
            return;
        }

        try {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['reporting_to']);
            });
        } catch (\Throwable $e) {
            // Ignore when the foreign key is absent.
        }

        DB::statement('UPDATE employees SET reporting_to = reporting_to_legacy_user_id');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('reporting_to_legacy_user_id');
        });
    }
};