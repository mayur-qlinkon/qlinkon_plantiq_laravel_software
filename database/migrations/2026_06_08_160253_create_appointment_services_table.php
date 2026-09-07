<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_services', function (Blueprint $table) {
            $table->id();

            // SaaS Tenant Isolation
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Core
            $table->string('name');
            $table->text('description')->nullable();

            // Pricing — nullable for free/enquiry-only services
            $table->decimal('price', 12, 2)->nullable();

            // Duration in minutes (e.g. 60 = 1 hour visit)
            $table->unsignedSmallInteger('duration_minutes')->nullable();

            // Display
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_services');
    }
};