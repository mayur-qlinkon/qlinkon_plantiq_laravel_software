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
        Schema::create('challan_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challan_return_id')->constrained('challan_returns')->cascadeOnDelete();
            $table->foreignId('challan_item_id')->constrained('challan_items')->cascadeOnDelete();

            $table->decimal('qty_returned', 10, 2);
            $table->decimal('qty_damaged', 10, 2)->default(0);  // damaged doesn't count as clean return
            $table->text('damage_note')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challan_return_items');
    }
};
