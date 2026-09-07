<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hrm_task_id')->nullable()->constrained()->nullOnDelete();

            $table->date('log_date');
            $table->decimal('hours_worked', 5, 2);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->text('description');
            $table->string('category', 50)->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('submitted');

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('admin_remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'log_date']);
            $table->index(['employee_id', 'log_date']);
            $table->index(['company_id', 'status']);
            $table->index(['employee_id', 'hrm_task_id']);
            $table->unique(['employee_id', 'log_date', 'start_time'], 'work_logs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_logs');
    }
};
