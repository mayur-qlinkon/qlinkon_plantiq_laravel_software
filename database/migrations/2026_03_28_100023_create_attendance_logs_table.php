<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('action', ['check_in', 'check_out']);
            $table->enum('method', ['qr', 'manual', 'auto', 'biometric'])->default('qr');
            $table->timestamp('punched_at');

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Device-reported GPS accuracy (meters) at the time of this scan attempt.
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            // Computed distance (meters) from the store's office location for this attempt.
            $table->decimal('distance_meters', 8, 2)->nullable();

            $table->string('device_info', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->text('remarks')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->string('rejection_reason', 255)->nullable();

            $table->timestamps();

            $table->index(['company_id', 'employee_id', 'punched_at']);
            $table->index(['attendance_id']);
            $table->index(['company_id', 'punched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
