<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            // SaaS Tenant Isolation
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Auto-generated reference number e.g. APT-202606-00001
            $table->string('appointment_no', 30)->nullable();

            // Relations
            $table->foreignId('service_id')
                  ->constrained('appointment_services')
                  ->restrictOnDelete();

            $table->foreignId('slot_id')
                  ->constrained('appointment_slots')
                  ->restrictOnDelete();

            // Booking date (date only — time is in slot)
            $table->date('appointment_date');

            // Customer info
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->string('customer_email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();

            // Status — stored as string for future-proofing
            $table->string('status', 30)->default('pending');

            // Admin notes (internal)
            $table->text('admin_notes')->nullable();

            $table->timestamps();

            // UNIQUE: one booking per slot per date per company
            // Cancelled bookings bypass this at query level (not DB level)
            $table->index(['company_id', 'appointment_date', 'slot_id', 'status']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'appointment_date']);
            $table->unique(['appointment_no', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};