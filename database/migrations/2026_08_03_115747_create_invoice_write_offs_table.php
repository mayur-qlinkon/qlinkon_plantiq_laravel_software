<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // INVOICE WRITE OFFS TABLE (Kasar Document)
        // One write-off record represents a non-cash amount the business
        // has decided not to collect against a specific invoice.
        // This never touches GST, stock, or the invoice grand_total.
        Schema::create('invoice_write_offs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();

            // Which invoice this Kasar belongs to
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();

            $table->foreignId('customer_id')->nullable()->constrained('clients')->restrictOnDelete();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            // E.g., WO-2608-0001
            $table->string('write_off_number', 50);
            $table->date('write_off_date');

            // Non-cash amount being written off
            $table->decimal('amount', 15, 2)->default(0);

            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft')->index();

            $table->enum('reason', [
                'customer_goodwill',
                'rounding',
                'bad_debt',
                'dispute_settlement',
                'other',
            ])->default('other');

            $table->text('notes')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Hot path index: used by outstanding-balance calculation
            $table->index(['company_id', 'invoice_id', 'status']);
            $table->index(['company_id', 'store_id']);
            $table->unique(['company_id', 'write_off_number']);
            $table->index(['company_id', 'write_off_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_write_offs');
    }
};