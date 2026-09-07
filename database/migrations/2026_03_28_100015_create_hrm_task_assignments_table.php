<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hrm_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('assigned_at')->useCurrent();
            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->unique(['hrm_task_id', 'employee_id']);
            $table->index(['employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_task_assignments');
    }
};
