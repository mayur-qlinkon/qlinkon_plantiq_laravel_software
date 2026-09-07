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
        Schema::create('challans', function (Blueprint $table) {
            $table->id();

            // ── Tenancy ──
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();

            // ── Challan Identity ──
            // FIX 1: composite unique — different companies can have DC-2025-0001
            $table->string('challan_number', 50);
            $table->unique(['company_id', 'challan_number']);

            $table->date('challan_date');
            $table->enum('challan_type', [
                'delivery',
                'job_work_out',
                'job_work_in',
                'branch_transfer',
                'sale_on_approval',
                'consignment',
                'repair_out',
                'exhibition',
                'returnable',
                'non_returnable',
            ]);

            $table->enum('direction', ['outward', 'inward'])->default('outward');

            // ── GST / State ──
            // FIX 2: keep is_inter_state — valid denormalization, computed in model boot
            // Never recompute on every query, too expensive at scale
            $table->foreignId('from_state_id')->nullable()->constrained('states');
            $table->foreignId('to_state_id')->nullable()->constrained('states');
            $table->boolean('is_inter_state')->default(false); // KEEP — set by model, never manually

            // ── Party ──
            // FIX 4: replace polymorphic with simple nullable FKs
            // Easier to query, index, debug, and join for reports
            // NULL = walk-in / unknown party
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            // branch_to = for branch transfers (party is another store)
            $table->foreignId('branch_store_id')->nullable()->constrained('stores')->nullOnDelete();
            // ── Warehouse Tracking ──
            // warehouse_id = Origin/Source (Where stock is currently sitting)
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();

            // to_warehouse_id = Destination (Required for Branch Transfers)
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();

            // ── Party Snapshot (always needed for PDF printing) ──
            // Regardless of party model, snapshot at dispatch time
            $table->string('party_name')->nullable();
            $table->text('party_address')->nullable();
            $table->string('party_gst', 15)->nullable();
            $table->string('party_phone', 20)->nullable();
            $table->string('party_state')->nullable();

            // ── Transport ──
            $table->string('transport_mode', 20)->nullable(); // road, rail, air, ship
            $table->string('transport_name')->nullable();
            $table->string('vehicle_number', 20)->nullable();
            $table->string('lr_number', 50)->nullable();
            $table->string('eway_bill_number', 20)->nullable();
            $table->date('eway_bill_expiry')->nullable();

            // ── Return Tracking ──
            $table->boolean('is_returnable')->default(false);
            $table->date('return_due_date')->nullable();
            $table->date('return_received_date')->nullable();

            // FIX 3: remove invoice_id from challans
            // One challan → many invoices (partial billing) — tracked via challan_items.invoice_item_id
            // Use Challan::whereHas('items', fn($q) => $q->whereNotNull('invoice_item_id')) to find converted

            // ── Source Document (kept as polymorphic — this one is justified) ──
            // Source is read-only backlink, never queried for reports, safe as polymorphic
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->index(['source_type', 'source_id']);

            // ── Status ──
            // Keeping all states — partially_returned and partially_converted are real business states
            $table->enum('status', [
                'draft',
                'dispatched',
                'in_transit',
                'delivered',
                'partially_returned',
                'fully_returned',
                'converted_to_invoice',
                'partially_converted',
                'closed',
                'cancelled',
            ])->default('draft');

            // ── Financials ──
            $table->decimal('total_qty', 10, 2)->default(0);
            $table->decimal('total_value', 12, 2)->default(0);

            // ── Notes ──
            $table->text('purpose_note')->nullable();
            $table->text('internal_notes')->nullable();

            // ── Delivery Confirmation ──
            $table->string('received_by')->nullable();
            $table->timestamp('delivered_at')->nullable();

            // ── Audit ──
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // FIX 5: Performance indexes
            $table->index(['company_id', 'challan_date']);  // date range reports
            $table->index(['company_id', 'status']);         // open challan dashboard
            $table->index(['company_id', 'challan_type']);   // type-wise filtering
            $table->index(['store_id', 'status']);            // branch-level views
            $table->index('is_returnable');                  // overdue return queries
            $table->index('return_due_date');                // aging alerts
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challans');
    }
};
