<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();

            // 🛡️ Fallback Safe Relations: Set to nullOnDelete so if a product is deleted
            // 2 years from now, the old quotation item text and prices remain intact.
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_sku_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();

            // 📸 Snapshots (CRITICAL for historical accuracy)
            $table->string('product_name');
            $table->string('sku_code', 100)->nullable();
            $table->string('hsn_code', 50)->nullable();

            $table->decimal('quantity', 10, 4);
            $table->decimal('unit_price', 15, 4);

            $table->enum('tax_type', ['inclusive', 'exclusive'])->default('exclusive');

            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('discount_value', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);

            $table->decimal('taxable_value', 15, 4)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);

            $table->decimal('cgst_amount', 15, 4)->default(0);
            $table->decimal('sgst_amount', 15, 4)->default(0);
            $table->decimal('igst_amount', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);

            $table->decimal('total_amount', 15, 4)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
