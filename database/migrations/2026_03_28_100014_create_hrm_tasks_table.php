<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('project', 100)->nullable();
            $table->string('category', 50)->nullable();

            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'in_review', 'completed', 'cancelled', 'on_hold'])->default('pending');

            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);

            $table->text('completion_note')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern', 50)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'due_date']);
            $table->index(['company_id', 'priority', 'status']);
            $table->index(['created_by', 'status']);
            $table->index(['company_id', 'project']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_tasks');
    }
};
