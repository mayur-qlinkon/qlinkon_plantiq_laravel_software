<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_plans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->constrained('users');

            $table->string('title');

            // Optional soft label — does NOT affect workflow.
            // Enum kept here so the UI can show a consistent dropdown.
            // Values: seasonal, customer_order, stock_replenishment,
            //         trial, forecast, other
            $table->string('purpose')->nullable();

            $table->text('notes')->nullable();

            // Draft → Confirmed → Cancelled.
            // Only three states in V1. Closed/Completed deferred.
            $table->string('status')->default('draft')->index();

            // Nullable: populated the moment status moves to confirmed.
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->foreignId('confirmed_by')
                ->nullable()
                ->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_plans');
    }
};