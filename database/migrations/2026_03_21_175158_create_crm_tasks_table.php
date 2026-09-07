<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('crm_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();            
            $table->foreignId('crm_lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('type', [
                'follow_up',
                'call',
                'meeting',
                'whatsapp',
                'email',
                'demo',
                'other',
            ])->default('follow_up');

            $table->enum('status', [
                'pending',
                'in_progress',
                'completed',
                'cancelled',
                'overdue',        // set by scheduler
            ])->default('pending')->index();

            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');

            $table->timestamp('due_at');               // when it must be done
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_note')->nullable();

            // Reminder
            $table->boolean('reminder_sent')->default(false);
            $table->timestamp('remind_at')->nullable();    // when to send WhatsApp reminder

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status', 'due_at']);
            $table->index(['assigned_to', 'status']);
            $table->index(['remind_at', 'reminder_sent']);  // for scheduler query
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_tasks');
    }
};
