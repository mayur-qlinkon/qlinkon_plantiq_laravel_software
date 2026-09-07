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
        // Separate table because a challan can have MULTIPLE partial returns over time
        Schema::create('challan_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challan_id')->constrained('challans')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();

            $table->string('return_number', 50)->unique(); // CR-2025-0001
            $table->date('return_date');

            $table->string('received_by')->nullable();
            $table->string('vehicle_number', 20)->nullable();
            $table->text('notes')->nullable();

            $table->enum('condition', [
                'good',       // all returned in original condition
                'damaged',    // some/all returned damaged
                'partial',    // only some returned this trip
            ])->default('good');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challan_returns');
    }
};
