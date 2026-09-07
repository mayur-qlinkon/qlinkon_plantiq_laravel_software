<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_plant_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            // System-generated on create. Format: PLB-YYYY-XXXX. Immutable after creation.
            $table->string('batch_code', 20);

            // restrictOnDelete: a product referenced by a live batch cannot be deleted.
            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();
                
            $table->foreignId('product_sku_id')
                ->nullable()                
                ->constrained('product_skus')
                ->nullOnDelete();

            // How this batch entered the nursery.
            // Values: production_plan | purchase | opening_stock | branch_transfer | other
            $table->string('source_type');

            // Soft polymorphic reference — no enforced FK.
            // production_plan  → production_plan_items.id
            // purchase         → purchase_items.id
            // opening_stock    → null
            // branch_transfer  → null (module not built yet)
            // other            → null
            $table->unsignedBigInteger('source_reference_id')->nullable();

            // initial_quantity: set once at creation, NEVER modified by any operation.
            // current_quantity: live count — decremented only by BatchLoss and BatchHarvest.
            $table->unsignedInteger('initial_quantity');
            $table->unsignedInteger('current_quantity');

            // Values: active | closed | cancelled
            $table->string('status')->default('active');

            // Business date when plants physically entered the nursery.                        
            $table->dateTime('batch_start_datetime')->nullable();

            // batch_end_datetime: auto-set by the service when a batch
            // transitions to Closed or Cancelled. Null while Active.
            $table->dateTime('batch_end_datetime')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // ── Indexes ───────────────────────────────────────────────
            $table->unique(['company_id', 'batch_code']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'source_type']);
            $table->index(['company_id', 'product_id']);
            $table->index(['company_id', 'batch_start_datetime']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_plant_batches');
    }
};