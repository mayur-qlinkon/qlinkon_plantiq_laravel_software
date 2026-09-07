<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_component_id')->constrained()->restrictOnDelete();

            $table->enum('calculation_type', ['fixed', 'percentage'])->default('fixed');
            $table->string('percentage_of', 30)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['employee_id', 'salary_component_id', 'effective_from'], 'emp_sal_struct_unique');

            // Provide a shorter custom name for the index (e.g., 'emp_sal_struct_active_idx')
            $table->index(['company_id', 'employee_id', 'is_active'], 'emp_sal_struct_active_idx');

            // This one is 63 characters (safe), but you can shorten it too for consistency
            $table->index(['employee_id', 'effective_from'], 'emp_sal_struct_eff_from_idx');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_structures');
    }
};
