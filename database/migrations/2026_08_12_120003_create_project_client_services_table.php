<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A service instance sold to one client — the ENTITLEMENT. Answers "is this
 * client's hosting currently valid?", never "has it been paid for?".
 *
 * The name and billing fields are snapshots taken at the time of sale. If the
 * catalog entry is later renamed or repriced, what was sold stays as it was.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_client_services', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            // Optional parent. A standalone domain renewal needs no project.
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();

            // Optional catalog origin. Null for ad-hoc custom services, and set
            // to null if the catalog row is later deleted — the snapshot below
            // keeps this record readable either way.
            $table->foreignId('service_id')->nullable()->constrained('project_services')->nullOnDelete();

            // Snapshot of what was sold.
            $table->string('name');
            $table->string('billing_cycle', 50)->default('one_time');
            $table->unsignedInteger('duration_days')->nullable();

            // What the NEXT renewal will cost. Amounts already charged live on
            // project_charges and are never touched by changes here.
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(18);

            // active, expired, cancelled
            // No paused state: a hosting plan is either running or it is not.
            $table->string('status', 30)->default('active');

            $table->date('started_at')->nullable();
            $table->date('current_period_start')->nullable();
            $table->date('current_period_end')->nullable();

            // Off by default. Auto-generating a charge the client never agreed
            // to is worse than forgetting to raise one.
            $table->boolean('auto_renew')->default(false);

            $table->date('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'store_id']);
            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'project_id']);
            $table->index(['company_id', 'status']);

            // Drives the "expiring in the next 30 days" and "overdue renewal"
            // dashboard lists.
            $table->index(
                ['company_id', 'status', 'current_period_end'],
                'pcs_status_period_end_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_client_services');
    }
};