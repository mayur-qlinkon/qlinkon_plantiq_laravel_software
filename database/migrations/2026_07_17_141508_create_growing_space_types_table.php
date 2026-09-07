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
        Schema::create('production_growing_space_types', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name'); // Greenhouse, Polyhouse, Open Field, Bench, Tray, Hydroponic Channel...

            // House style: constants + LABELS map on the model, not a
            // native enum. See
            // App\Models\Production\GrowingSpaceType::CAPACITY_UNIT_*
            $table->string('capacity_unit');
            $table->string('custom_unit_label')
                ->nullable()
                ->comment('Used only when capacity_unit = custom');

            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_growing_space_types');
    }
};