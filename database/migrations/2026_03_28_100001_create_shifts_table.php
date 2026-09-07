<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('name', 100);
            $table->string('code', 20)->nullable();
            $table->text('description')->nullable();

            $table->time('start_time');
            $table->time('end_time');
            $table->time('late_mark_after')->nullable();
            $table->time('early_leave_before')->nullable();
            $table->time('half_day_after')->nullable();

            $table->unsignedSmallInteger('break_duration_minutes')->default(0);
            $table->unsignedSmallInteger('min_working_hours_minutes')->default(480);
            $table->unsignedSmallInteger('overtime_after_minutes')->nullable();

            $table->boolean('is_night_shift')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
