<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            $table->string('slip_number', 50);
            $table->unsignedSmallInteger('month');
            $table->year('year');

            $table->unsignedSmallInteger('working_days')->default(0);
            $table->unsignedSmallInteger('present_days')->default(0);
            $table->unsignedSmallInteger('absent_days')->default(0);
            $table->decimal('leave_days', 5, 1)->default(0);
            $table->decimal('overtime_hours', 6, 2)->default(0);

            $table->decimal('gross_earnings', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->decimal('round_off', 8, 2)->default(0);

            $table->enum('payment_mode', ['bank_transfer', 'cash', 'cheque', 'upi'])->nullable();
            $table->foreignId('payment_method_id')
                ->nullable()
                ->constrained('payment_methods')
                ->nullOnDelete();

            $table->string('payment_method_name', 100)
                ->nullable()                
                ->comment('Snapshot of PaymentMethod->label at the time of payment');
            $table->string('payment_reference', 100)->nullable();
            $table->date('payment_date')->nullable();

            $table->enum('status', ['draft', 'generated', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'employee_id', 'month', 'year'], 'salary_slips_unique');
            $table->unique(['company_id', 'slip_number']);
            $table->index(['company_id', 'month', 'year']);
            $table->index(['company_id', 'status']);
            $table->index(['employee_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_slips');
    }
};
