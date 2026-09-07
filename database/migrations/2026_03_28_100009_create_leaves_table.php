<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();

            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('total_days', 5, 1);
            $table->enum('day_type', ['full_day', 'first_half', 'second_half'])->default('full_day');

            $table->text('reason');
            $table->string('document')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('admin_remarks')->nullable();

            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['employee_id', 'status']);
            $table->index(['company_id', 'from_date', 'to_date']);
            $table->index(['employee_id', 'from_date']);
            $table->index(['leave_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
