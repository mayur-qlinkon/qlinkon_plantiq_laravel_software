<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->enum('type', ['single', 'variable'])->default('single');
            $table->enum('product_type', ['sellable', 'catalog'])->default('sellable');
            $table->string('barcode_symbology', 50)->default('CODE128');
            $table->string('hsn_code', 20)->nullable()->index();
            $table->foreignId('product_unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('sale_unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('purchase_unit_id')->constrained('units')->restrictOnDelete();

            $table->integer('quantity_limitation')->nullable();
            $table->text('note')->nullable();
            $table->text('description')->nullable();
            $table->json('specifications')->nullable();
            $table->json('product_guide')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_storefront')->default(true);
            $table->boolean('show_as_addon')->default(false);
            $table->unsignedInteger('total_sold')->default(0);
            $table->index(['company_id', 'total_sold']);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
