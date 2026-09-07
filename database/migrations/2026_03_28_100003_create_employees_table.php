<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->foreignId('reporting_to')->nullable()->constrained('employees')->nullOnDelete();

            // Identity
            $table->string('employee_code', 30);
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->string('blood_group', 5)->nullable();

            // Employment
            $table->date('date_of_joining');
            $table->date('date_of_leaving')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern', 'freelancer'])->default('full_time');
            $table->enum('status', ['active', 'inactive', 'terminated', 'on_notice', 'absconding'])->default('active');
            $table->text('exit_reason')->nullable();

            // Base Salary Reference
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->enum('salary_type', ['monthly', 'daily', 'hourly'])->default('monthly');

            // Bank Details
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 30)->nullable();
            $table->string('bank_ifsc', 20)->nullable();
            $table->string('bank_branch', 100)->nullable();

            // Indian Statutory
            $table->text('pan_number')->nullable();
            $table->text('aadhaar_number')->nullable();
            $table->string('uan_number', 20)->nullable();
            $table->string('esi_number', 20)->nullable();
            $table->string('pf_number', 30)->nullable();

            // Address
            $table->text('current_address')->nullable();
            $table->text('permanent_address')->nullable();

            // Emergency Contact
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('emergency_contact_relation', 50)->nullable();

            // Documents
            $table->string('photo')->nullable();
            $table->string('id_proof')->nullable();
            $table->string('address_proof')->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'user_id']);
            $table->unique(['company_id', 'employee_code']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'store_id']);
            $table->index(['company_id', 'department_id']);
            $table->index(['company_id', 'designation_id']);
            $table->index(['company_id', 'date_of_joining']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
