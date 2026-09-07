<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links money to charges. This is what makes "one payment across many charges"
 * and "many payments against one charge" both true at the same time — the exact
 * capability the legacy module lacked, and the reason partially paid renewals
 * could not be represented.
 *
 * The `kind` column keeps this table open to non-cash settlement without a
 * future migration on a financial table:
 *
 *   payment   — cash actually received, linked to a payments row
 *   tds       — tax deducted at source by the client (payment_id is null)
 *   write_off — amount intentionally forgiven, the Kasar pattern
 *   credit    — applied from a client's unallocated advance
 *
 * Only 'payment' and 'write_off' are used today. TDS is common for Indian B2B
 * service billing (194J/194C) even though this tenant's clients do not deduct
 * it, so the shape is reserved now rather than retrofitted later.
 *
 * Financial rows are never hard deleted. Reversal is a flag, so the audit trail
 * survives.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_charge_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->foreignId('charge_id')->constrained('project_charges')->cascadeOnDelete();

            // Null for kind = tds / write_off, which settle a charge without any
            // money arriving.
            //
            // A payment's paymentable answers "where did this come from"; these
            // allocations answer "what did it settle". They are different
            // questions, which is why one payment can span several charges.
            $table->foreignId('payment_id')->nullable()->constrained('payments')->restrictOnDelete();

            // payment, tds, write_off
            //
            // Only payment and write_off are used today. TDS (194J/194C) is
            // reserved so a future tenant needing it does not require a
            // migration on a live financial table.
            $table->string('kind', 20)->default('payment');

            $table->decimal('amount', 15, 2);

            // auto (FIFO) or manual — for reporting on how allocation happened.
            $table->string('source', 20)->default('manual');

            // Required for write_off, optional otherwise.
            $table->string('reason')->nullable();

            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('allocated_at')->nullable();

            $table->boolean('is_reversed')->default(false);
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            // Rebuilding a charge's cached paid/written-off totals.
            $table->index(['charge_id', 'is_reversed', 'kind']);

            // Listing what a single payment was applied to, and computing a
            // client's unallocated credit balance.
            $table->index(['payment_id', 'is_reversed']);

            $table->index(['company_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_charge_allocations');
    }
};