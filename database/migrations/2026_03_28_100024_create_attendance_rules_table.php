<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('name', 100);
            $table->string('code', 30)->nullable();
            $table->text('description')->nullable();

            $table->enum('rule_type', [
                'late_to_half_day',
                'late_to_absent',
                'absent_to_leave',
                'early_leave_penalty',
                'continuous_absent_action',
                'overtime_rule',
                'custom',
            ]);

            $table->unsignedSmallInteger('threshold_count')->default(3);
            $table->enum('threshold_period', ['weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->enum('action', ['mark_half_day', 'mark_absent', 'deduct_leave', 'send_warning', 'notify_manager'])->default('mark_half_day');
            $table->decimal('deduction_days', 5, 1)->nullable()->default(0);
            $table->string('leave_type_code', 20)->nullable();

            $table->boolean('auto_apply')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'rule_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_rules');
    }
};
