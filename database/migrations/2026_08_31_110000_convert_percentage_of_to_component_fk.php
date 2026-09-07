<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // `percentage_of` held a component CODE as a plain string. That is the
        // same class of bug as the hard-coded 'BASIC' lookup in SalaryService:
        // a typo or a later rename silently resolves to nothing, and the
        // engine falls back to a default instead of failing. A foreign key
        // makes the reference unbreakable and gives Phase 2's dependency
        // graph real node IDs to walk instead of strings to match.

        Schema::table('salary_components', function (Blueprint $table) {
            $table->foreignId('percentage_of_component_id')
                ->nullable()
                ->after('calculation_type')
                ->constrained('salary_components')
                ->restrictOnDelete();
        });

        Schema::table('employee_salary_structures', function (Blueprint $table) {
            $table->foreignId('percentage_of_component_id')
                ->nullable()
                ->after('calculation_type')
                ->constrained('salary_components')
                ->restrictOnDelete();
        });

        // Backfill by matching the stored code within the same company.
        // Written with the query builder rather than UPDATE...JOIN so it runs
        // on SQLite too, which the test suite uses.
        $this->backfill('salary_components');
        $this->backfill('employee_salary_structures');

        // Any code that matched nothing is reported rather than dropped
        // quietly — an unresolvable reference was producing a wrong salary,
        // so it needs to be seen before the old column disappears.
        $this->reportOrphans('salary_components');
        $this->reportOrphans('employee_salary_structures');

        Schema::table('salary_components', function (Blueprint $table) {
            $table->dropColumn('percentage_of');
        });

        Schema::table('employee_salary_structures', function (Blueprint $table) {
            $table->dropColumn('percentage_of');
        });
    }

    protected function backfill(string $table): void
    {
        $rows = DB::table($table)
            ->whereNotNull('percentage_of')
            ->get(['id', 'company_id', 'percentage_of']);

        foreach ($rows as $row) {
            $baseId = DB::table('salary_components')
                ->where('company_id', $row->company_id)
                ->where('code', $row->percentage_of)
                ->whereNull('deleted_at')
                ->value('id');

            if ($baseId) {
                DB::table($table)->where('id', $row->id)
                    ->update(['percentage_of_component_id' => $baseId]);
            }
        }
    }

    protected function reportOrphans(string $table): void
    {
        $orphans = DB::table($table)
            ->whereNotNull('percentage_of')
            ->whereNull('percentage_of_component_id')
            ->pluck('percentage_of', 'id');

        foreach ($orphans as $id => $code) {
            echo "  [warn] {$table} #{$id}: percentage_of \"{$code}\" matched no component — reference cleared.".PHP_EOL;
        }
    }

    public function down(): void
    {
        Schema::table('salary_components', function (Blueprint $table) {
            $table->string('percentage_of', 30)->nullable()->after('calculation_type');
            $table->dropConstrainedForeignId('percentage_of_component_id');
        });

        Schema::table('employee_salary_structures', function (Blueprint $table) {
            $table->string('percentage_of', 30)->nullable()->after('calculation_type');
            $table->dropConstrainedForeignId('percentage_of_component_id');
        });
    }
};