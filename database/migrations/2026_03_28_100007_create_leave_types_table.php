<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('name', 100);
            $table->string('code', 20);
            $table->text('description')->nullable();

            $table->decimal('default_days_per_year', 5, 1)->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_carry_forward')->default(false);
            $table->decimal('max_carry_forward_days', 5, 1)->nullable()->default(0);
            $table->boolean('is_encashable')->default(false);
            $table->boolean('requires_document')->default(false);
            $table->unsignedSmallInteger('min_days_before_apply')->nullable()->default(0);
            $table->decimal('max_consecutive_days', 5, 1)->default(0);
            $table->enum('applicable_gender', ['all', 'male', 'female'])->default('all');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
