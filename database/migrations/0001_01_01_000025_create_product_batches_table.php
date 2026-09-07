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
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();            
            $table->foreignId('product_sku_id')
                ->constrained('product_skus')
                ->cascadeOnDelete();
            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete();
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->integer('qty')->default(0);
            $table->integer('remaining_qty')->default(0);
            $table->boolean('is_active')->default(true);
            $table->index(['product_sku_id', 'warehouse_id', 'expiry_date']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};
