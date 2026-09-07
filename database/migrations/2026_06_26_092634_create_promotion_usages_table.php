<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks every time a promotion is actually redeemed.
     * Enables per-customer usage limits + a full audit trail.
     */
    public function up(): void
    {
        Schema::create('promotion_usages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('promotion_id')
                ->constrained()
                ->cascadeOnDelete();

            // The tenant company this promotion belongs to (denormalized for fast queries).
            // Nullable because platform-level promotions have no owning company.
            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Who redeemed it — a storefront customer (client). Nullable so the same
            // table can later record platform/company-level redemptions too.
            $table->foreignId('client_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Optional polymorphic link to whatever the promotion was applied to
            // (Order, Invoice, CompanySubscription...) — write-once, use-anywhere.
            $table->nullableMorphs('usable');

            // Snapshot of the discount actually given (audit — promotion may change later).
            $table->decimal('discount_amount', 12, 2)->default(0);

            $table->timestamps();

            // Fast lookup for "how many times has this client used this promo".
            $table->index(['promotion_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_usages');
    }
};