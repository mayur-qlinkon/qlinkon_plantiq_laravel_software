<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_zone_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained('production_zones')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->timestamp('assigned_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Same employee ke multiple active zones ho sakte hain,
            // isliye composite unique nahi lagaya — duplicate guard
            // ZoneAssignmentService mein hai (business rule, DB constraint nahi).
            $table->index(['zone_id', 'is_active']);
            $table->index(['employee_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_zone_assignments');
    }
};