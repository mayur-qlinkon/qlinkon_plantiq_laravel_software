<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only assignment history.
     *
     * crm_lead_assignees stays as the current-state pivot — it answers "who
     * holds this lead now" and every existing query reads it. This table answers
     * "who held it, from when, until when, and who handed it over", which the
     * pivot cannot: assignTo() syncs, and sync deletes the previous row.
     *
     * Without this, a lead reassigned after three weeks of work credits all of
     * that work to whoever received it last.
     */
    public function up(): void
    {
        Schema::create('crm_lead_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crm_lead_id')->constrained()->cascadeOnDelete();

            // Who received the lead.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Who handed it over. Null means the system did it (import, auto-assign).
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('is_primary')->default(true);

            $table->timestamp('assigned_at');

            // Null = still holding it. Exactly one open row per lead.
            $table->timestamp('unassigned_at')->nullable();

            $table->timestamps();

            // Drives the per-employee report: rows for user X in a date window.
            $table->index(['company_id', 'user_id', 'assigned_at']);

            // Finding the open row when reassigning, and the lead's timeline.
            $table->index(['crm_lead_id', 'unassigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_lead_assignments');
    }
};