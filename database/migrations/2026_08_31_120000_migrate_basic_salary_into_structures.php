<?php

use App\Models\Hrm\SalaryComponent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Basic pay lived in two places: employees.basic_salary and, for some
        // companies, a component row as well. The engine synthesised a Basic
        // line whenever it could not find the literal code 'BASIC', so a
        // company that coded it anything else got both — the duplicate line
        // seen on the payslip. This moves every employee's basic into the
        // structure so there is one source of truth for the engine to read.
        //
        // Model events and the tenant scope are bypassed throughout: this runs
        // across all companies with no authenticated user, and the
        // singleton-role guard would reject the very rows it is meant to
        // create.

        $companies = DB::table('employees')
            ->select('company_id')
            ->distinct()
            ->pluck('company_id');

        foreach ($companies as $companyId) {
            $basicId = $this->resolveBasicComponent($companyId);

            $this->migrateEmployees($companyId, $basicId);
            $this->repointOrphanedPercentages($companyId, $basicId);
        }
    }

    /**
     * Find the company's Basic component, promoting an existing one where the
     * name or code makes the intent obvious, otherwise creating it.
     */
    protected function resolveBasicComponent(int $companyId): int
    {
        $existing = DB::table('salary_components')
            ->where('company_id', $companyId)
            ->where('role', SalaryComponent::ROLE_BASIC)
            ->whereNull('deleted_at')
            ->value('id');

        if ($existing) {
            return $existing;
        }

        $candidate = DB::table('salary_components')
            ->where('company_id', $companyId)
            ->where('type', SalaryComponent::TYPE_EARNING)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('code', 'BASIC')
                    ->orWhere('code', 'BS')
                    ->orWhere('name', 'like', '%basic%');
            })
            ->orderBy('id')
            ->first();

        if ($candidate) {
            DB::table('salary_components')
                ->where('id', $candidate->id)
                ->update([
                    'role' => SalaryComponent::ROLE_BASIC,
                    'sort_order' => 0,
                    'updated_at' => now(),
                ]);

            echo "  [info] company {$companyId}: promoted component #{$candidate->id} ({$candidate->code}) to role basic.".PHP_EOL;

            return $candidate->id;
        }

        $id = DB::table('salary_components')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Basic Salary',
            'code' => 'BASIC',
            'type' => SalaryComponent::TYPE_EARNING,
            'role' => SalaryComponent::ROLE_BASIC,
            'calculation_type' => SalaryComponent::CALC_FIXED,
            'default_amount' => 0,
            'is_taxable' => true,
            'is_statutory' => true,
            'appears_on_payslip' => true,
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "  [info] company {$companyId}: created Basic Salary component #{$id}.".PHP_EOL;

        return $id;
    }

    protected function migrateEmployees(int $companyId, int $basicId): void
    {
        $employees = DB::table('employees')
            ->where('company_id', $companyId)
            ->where('basic_salary', '>', 0)
            ->get(['id', 'basic_salary', 'employee_code']);

        foreach ($employees as $employee) {
            $already = DB::table('employee_salary_structures')
                ->where('employee_id', $employee->id)
                ->where('salary_component_id', $basicId)
                ->where('is_active', true)
                ->first(['id', 'amount']);

            if ($already) {
                // The column and the row disagreed for exactly the employees
                // hit by the duplicate-line bug. The row is the figure HR
                // entered against the component, so the row wins.
                if ((float) $already->amount !== (float) $employee->basic_salary) {
                    echo "  [warn] {$employee->employee_code}: column ₹{$employee->basic_salary} vs structure ₹{$already->amount} — keeping structure value.".PHP_EOL;
                }

                continue;
            }

            // effective_from is left null so the row applies to every payroll
            // period, including months already generated. A dated value would
            // silently exclude back-dated runs.
            DB::table('employee_salary_structures')->insert([
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'salary_component_id' => $basicId,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'amount' => $employee->basic_salary,
                'effective_from' => null,
                'effective_to' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            echo "  [info] {$employee->employee_code}: basic ₹{$employee->basic_salary} moved into salary structure.".PHP_EOL;
        }
    }

    /**
     * Percentage rows whose base reference was cleared by the FK migration
     * were all pointing at the code 'BASIC', so they belong to this component.
     */
    protected function repointOrphanedPercentages(int $companyId, int $basicId): void
    {
        $count = DB::table('employee_salary_structures')
            ->where('company_id', $companyId)
            ->where('calculation_type', SalaryComponent::CALC_PERCENTAGE)
            ->whereNull('percentage_of_component_id')
            ->update(['percentage_of_component_id' => $basicId, 'updated_at' => now()]);

        $count += DB::table('salary_components')
            ->where('company_id', $companyId)
            ->where('calculation_type', SalaryComponent::CALC_PERCENTAGE)
            ->whereNull('percentage_of_component_id')
            ->update(['percentage_of_component_id' => $basicId, 'updated_at' => now()]);

        if ($count > 0) {
            echo "  [info] company {$companyId}: repointed {$count} percentage reference(s) to Basic.".PHP_EOL;
        }
    }

    public function down(): void
    {
        // Not reversible: the original column values are superseded by the
        // structure rows, which HR may have edited since.
    }
};