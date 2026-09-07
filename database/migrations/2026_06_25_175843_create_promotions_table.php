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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            
            // Basic
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_apply')->default(false);

            // Discount
            $table->enum('discount_type', [
                'fixed',
                'percentage',
                'free_trial',
                'free_module',
                'free_limit',
            ]);

            $table->decimal('discount_value', 12, 2)->nullable();

            $table->decimal('max_discount', 12, 2)->nullable();

            // Conditions
            $table->decimal('minimum_order_amount', 12, 2)->nullable();

            $table->decimal('maximum_order_amount', 12, 2)->nullable();

            // Usage
            $table->unsignedInteger('usage_limit')->nullable();

            $table->unsignedInteger('usage_per_customer')->default(1);

            $table->unsignedInteger('times_used')->default(0);

            // Date
            $table->timestamp('starts_at')->nullable();

            $table->timestamp('expires_at')->nullable();

            // Priority
            $table->unsignedSmallInteger('priority')->default(100);

            // Flexible Rules
            $table->json('conditions')->nullable();

            // Flexible Reward
            $table->json('reward')->nullable();

            $table->unique(['company_id', 'code'], 'promotions_company_code_unique');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
