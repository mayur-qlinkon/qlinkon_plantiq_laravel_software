<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A billing obligation — the heart of the module. Every rupee owed in this
 * module comes from a charge, whether it originated from a project, a service
 * renewal, or a standalone one-off job.
 *
 * Immutable once created: amount, tax_rate, title and period must never be
 * edited. Correct a mistake by cancelling the charge and raising a new one,
 * so history stays trustworthy.
 *
 * A renewal is simply the next charge on the same client service. There is no
 * separate renewals table, which is what allows one payment to settle several
 * billing periods at once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_charges', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // NOT NULL to match payments.store_id, so a charge and the payment
            // settling it can never disagree about which store they belong to.
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();

            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            // Both parents optional — a charge can stand entirely on its own.
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('client_service_id')->nullable()->constrained('project_client_services')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('project_services')->nullOnDelete();

            // one_time, renewal, adjustment
            // Descriptive only — type never changes how money is allocated.
            $table->string('type', 30)->default('one_time');

            // Snapshot — readable even if every parent row is later deleted.
            $table->string('title');
            $table->text('description')->nullable();

            $table->date('charge_date');

            // Required for ageing buckets. The legacy module had no due date,
            // which is why collection follow-up was impossible.
            $table->date('due_date')->nullable();

            // Set only for recurring/renewal charges. A charge carrying a period
            // IS the renewal record for that period.
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();

            // Financial snapshot — frozen at creation.
            // taxable amount is always subtotal - discount, so it is derived in
            // the model rather than stored as a third value that can drift.
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);

            // Cached running totals. Source of truth is always the sum of
            // non-reversed project_charge_allocations rows; these exist so list
            // and dashboard screens do not aggregate on every page load.
            // A reconcile command should be able to rebuild both.
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('written_off_amount', 15, 2)->default(0);

            // pending, partially_paid, paid, written_off, cancelled
            //
            // Always DERIVED from the cached amounts above. Never set by hand,
            // or the system ends up holding two versions of the truth.
            $table->string('status', 30)->default('pending');

            // Cheque number, UTR, PO reference and similar.
            $table->string('reference')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'store_id']);
            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'project_id']);
            $table->index(['company_id', 'client_service_id']);

            // Outstanding lists and ageing reports.
            $table->index(['company_id', 'status', 'due_date']);

            // FIFO allocation: a client's unpaid charges, oldest due first.
            $table->index(['client_id', 'status', 'due_date']);

            // Renewal history for one service, in period order.
            $table->index(['client_service_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_charges');
    }
};