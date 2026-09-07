<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_plan_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('production_plan_id')
                ->constrained()
                ->cascadeOnDelete();

            // References the existing Product catalog.
            // restrictOnDelete: a product in use by a plan cannot be deleted
            // without first removing the plan item — intentional friction.
            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            // The only three things Planning is responsible for.
            $table->unsignedInteger('target_quantity');
            $table->date('target_date');
            $table->text('remarks')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['production_plan_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_plan_items');
    }
};