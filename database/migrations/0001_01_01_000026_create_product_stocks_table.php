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
        // The exact current snapshot of stock per warehouse
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();            
            $table->foreignId('product_sku_id')->constrained('product_skus')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();

            $table->integer('qty')->default(0);
            $table->string('rack_number')->nullable()->comment('e.g., A-12'); // Moved here from your old pivot!

            $table->timestamps();
            $table->unique(['product_sku_id', 'warehouse_id']); // A SKU can only have ONE stock record per warehouse
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};
