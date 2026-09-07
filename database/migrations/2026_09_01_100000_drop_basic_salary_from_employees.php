<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The salary structure is the single source of truth for pay. This
        // column was the second one, and the engine reading both is what
        // produced the duplicate Basic line on every payslip.
        //
        // salary_type stays: it is a wage basis (monthly / daily / hourly),
        // not an amount, and it decides which formula the engine uses at all.

        // Refuse to drop while anyone's basic has not reached the structure.
        // Once the column is gone the figure cannot be recovered.
        $unmigrated = DB::table('employees as e')
            ->where('e.basic_salary', '>', 0)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('employee_salary_structures as ess')
                    ->join('salary_components as sc', 'sc.id', '=', 'ess.salary_component_id')
                    ->whereColumn('ess.employee_id', 'e.id')
                    ->where('ess.is_active', true)
                    ->where('sc.role', 'basic');
            })
            ->pluck('e.employee_code');

        if ($unmigrated->isNotEmpty()) {
            throw new RuntimeException(
                'Aborting: these employees still hold a basic_salary with no Basic component '
                .'in their salary structure — '.$unmigrated->implode(', ').'. '
                .'Run the 2026_08_31_120000 migration first, or add the component by hand.'
            );
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('basic_salary');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('basic_salary', 12, 2)->default(0)->after('status');
        });

        // The column comes back empty. Values live in the salary structure now
        // and are not copied back, because HR may have edited them since.
    }
};