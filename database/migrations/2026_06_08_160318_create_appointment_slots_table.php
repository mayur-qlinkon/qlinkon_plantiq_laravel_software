<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_slots', function (Blueprint $table) {
            $table->id();

            // SaaS Tenant Isolation
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Display name e.g. "Morning Slot", "09:00 - 10:00"
            $table->string('slot_name');

            // Time range — stored as TIME (HH:MM:SS)
            $table->time('start_time');
            $table->time('end_time');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_slots');
    }
};