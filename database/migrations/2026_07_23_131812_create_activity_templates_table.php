<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_activity_templates', function (Blueprint $table) {
            $table->id();

            // Tenant
            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            // Plant species / Product
            $table->foreignId('product_id')
                ->nullable() 
                ->constrained('products')
                ->restrictOnDelete();

            /*
             |--------------------------------------------------------------------------
             | Activity
             |--------------------------------------------------------------------------
             | watering
             | fertilizer
             | inspection
             | dead_check
             | pruning
             | sorting
             | spraying
             | other
             */
            $table->string('activity_type', 30);

            /*
             |--------------------------------------------------------------------------
             | Frequency
             |--------------------------------------------------------------------------
             | interval  -> every X days (V1)
             |
             | Reserved for future:
             | daily
             | weekly
             | monthly
             */
            $table->string('frequency_type', 20)->default('interval');

            // Example:
            // interval + 1 = every day
            // interval + 3 = every 3 days
            // interval + 7 = every week
            $table->unsignedSmallInteger('frequency_value');

            /*
             |--------------------------------------------------------------------------
             | Delay after batch creation
             |--------------------------------------------------------------------------
             | Example:
             | Watering    -> 0
             | Fertilizer  -> 15
             */
            $table->unsignedSmallInteger('start_after_days')
                ->default(0);

            // Required task?
            // Example:
            // Dead Check = true
            // Pruning    = false
            $table->boolean('is_required')
                ->default(true);

            $table->boolean('is_active')
                ->default(true);

            // Employee checklist order
            $table->unsignedSmallInteger('sort_order')
                ->default(0);

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

           /*
             |--------------------------------------------------------------------------
             | Indexes
             |--------------------------------------------------------------------------
             */

            // One activity rule per Product
            // Added custom short name 'pat_unique_rule' to bypass 64-char limit
            $table->unique([
                'company_id',
                'product_id',
                'activity_type',
            ], 'pat_unique_rule'); 

            // Added custom short name 'pat_active_index'
            $table->index([
                'company_id',
                'product_id',
                'is_active',
            ], 'pat_active_index');

            // Optional: Shortened just to keep naming consistent
            $table->index([
                'company_id',
                'activity_type',
            ], 'pat_activity_index');

            // Optional: Shortened just to keep naming consistent
            $table->index([
                'company_id',
                'sort_order',
            ], 'pat_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_activity_templates');
    }
};