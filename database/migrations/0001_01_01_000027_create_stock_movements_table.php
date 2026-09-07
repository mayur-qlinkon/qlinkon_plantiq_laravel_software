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
        // The Immutable Ledger (History of everything that happens)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete(); // Crucial for querying speed
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_sku_id')->constrained('product_skus')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->foreignId('unit_id')->constrained('units');
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->enum('direction', ['in', 'out']);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('balance_after', 15, 4)->comment('Total stock in warehouse AFTER this move');
            $table->enum('movement_type', [
                'purchase', 'sale', 'purchase_return', 'sale_return',
                'adjustment', 'transfer_in', 'transfer_out', 'opening_stock',
                'production_harvest'
            ]);
            $table->string('reference_type')->nullable()->comment('e.g., App\Models\Invoice');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('note')->nullable(); // Brief reason for manual adjustments
            $table->timestamps();
            $table->index(['product_sku_id', 'warehouse_id']);
            $table->index(['company_id', 'movement_type']);
            $table->index(['reference_type', 'reference_id'], 'stock_movement_reference_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
