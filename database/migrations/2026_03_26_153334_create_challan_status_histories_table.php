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
        // Same pattern as your order_status_history
        Schema::create('challan_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challan_id')->constrained('challans')->cascadeOnDelete();

            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->string('changed_by_type')->default('admin'); // admin, system, driver
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challan_status_history');
    }
};
