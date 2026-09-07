<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_components', function (Blueprint $table) {
            // Semantic role for the payroll engine. Tenants may name and code
            // a component anything they like ("Mool Vetan" / "BS"), so the
            // engine must never match on name or code. Roles that carry
            // calculation meaning are constrained to one active component per
            // company (enforced in the model, not here, because the uniqueness
            // must ignore soft-deleted and inactive rows).
            $table->string('role', 30)
                ->default('other')
                ->after('type');

            $table->index(['company_id', 'role'], 'salary_components_company_role_idx');
        });

        // The generated column below is MySQL/MariaDB syntax. On other drivers
        // (SQLite, used by the test suite) the model guard in
        // SalaryComponent::booted() is the only protection — acceptable there,
        // since a test run has no concurrency for a unique index to catch.
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // DB-level guarantee that a company has at most one ACTIVE component
        // per meaningful role. The model hook alone is check-then-write and
        // two concurrent requests can pass it both. A generated column that
        // collapses to NULL for inactive, soft-deleted and 'other' rows lets a
        // plain unique index carry the constraint, since NULLs never collide.
        DB::statement("
            ALTER TABLE salary_components
            ADD COLUMN active_role_key VARCHAR(30)
                GENERATED ALWAYS AS (
                    CASE
                        WHEN is_active = 1 AND deleted_at IS NULL AND role <> 'other'
                        THEN role
                        ELSE NULL
                    END
                ) STORED
        ");

        DB::statement("
            ALTER TABLE salary_components
            ADD UNIQUE INDEX salary_components_active_role_unique (company_id, active_role_key)
        ");
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE salary_components DROP INDEX salary_components_active_role_unique');
            DB::statement('ALTER TABLE salary_components DROP COLUMN active_role_key');
        }

        Schema::table('salary_components', function (Blueprint $table) {
            $table->dropIndex('salary_components_company_role_idx');
            $table->dropColumn('role');
        });
    }
};