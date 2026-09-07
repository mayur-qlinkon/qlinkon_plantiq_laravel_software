<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();

            $table->date('date');

            // Check-in
            $table->timestamp('check_in_time')->nullable();
            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_lng', 10, 7)->nullable();
            $table->enum('check_in_method', ['qr', 'manual', 'auto', 'biometric'])->nullable();
            $table->boolean('check_in_location_verified')->nullable();
            $table->string('check_in_review_status')->nullable();
            $table->foreignId('check_in_reviewed_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('check_in_reviewed_at')->nullable();

            // Null/true = normal GPS-verified checkout (or no checkout yet).
            // False = employee used the "Request Checkout" GPS-fallback path.
            $table->boolean('check_out_location_verified')->nullable()->default(true);
 
            // pending | approved | rejected — only set when location_verified is false.
            $table->string('check_out_review_status')->nullable();
 
            $table->foreignId('check_out_reviewed_by')->nullable()
                ->constrained('users')->nullOnDelete();
 
            $table->timestamp('check_out_reviewed_at')->nullable();

            // Check-out
            $table->timestamp('check_out_time')->nullable();
            $table->decimal('check_out_lat', 10, 7)->nullable();
            $table->decimal('check_out_lng', 10, 7)->nullable();
            $table->enum('check_out_method', ['qr', 'manual', 'auto', 'biometric'])->nullable();

            // Computed
            $table->decimal('worked_hours', 5, 2)->nullable();
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->string('status')->default('present');
            $table->boolean('is_holiday')->default(false);
            $table->boolean('working_on_holiday')->default(false);

            // Admin Override
            $table->boolean('is_overridden')->default(false);
            $table->foreignId('overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('override_reason')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'employee_id', 'date']);
            $table->index(['company_id', 'date']);
            $table->index(['employee_id', 'date']);
            $table->index(['company_id', 'status', 'date']);
            $table->index(['store_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
