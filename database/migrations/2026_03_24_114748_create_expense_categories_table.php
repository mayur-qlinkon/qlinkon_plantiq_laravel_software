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
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('expense_categories')
                ->nullOnDelete();
            $table->string('name'); // e.g., 'Travel', 'Meals', 'Office Supplies'
            $table->string('color', 20)->nullable();
            $table->string('icon')->nullable();
            $table->string('description')->nullable();
            $table->enum('type', [
                'direct',     // e.g. purchase related
                'indirect',   // rent, salary
                'asset',      // capital expense
            ])->default('indirect');
            $table->enum('gst_type', [
                'taxable',
                'non_taxable',
                'exempt',
            ])->default('taxable');
            // 🌟 Indian Market Specific: SAC/HSN Code for accounting
            $table->string('account_code')->nullable(); // e.g., EXP-001
            $table->string('hsn_sac_code', 20)->nullable();
            $table->decimal('default_tax_rate', 5, 2)->default(0); // e.g., 18.00
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
