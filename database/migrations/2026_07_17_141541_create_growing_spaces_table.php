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
        Schema::create('production_growing_spaces', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            // Denormalized root reference, same rationale as production_zones.
            $table->foreignId('production_site_id')
                ->constrained()
                ->cascadeOnDelete();

            // Nullable: null means this space sits directly under the Site,
            // for tenants who skip zoning altogether.
            $table->foreignId('zone_id')
                ->nullable()
                ->constrained('production_zones')
                ->nullOnDelete();

            // Restricted, not nullOnDelete: a type still in use must be
            // reassigned before it can be removed, so a space never
            // silently loses its capacity-unit meaning.
            $table->foreignId('growing_space_type_id')
                ->constrained('production_growing_space_types')
                ->restrictOnDelete();

            $table->string('name'); // tenant-labeled, e.g. "GH1-R3-C5", "Bed A1"
            $table->decimal('capacity', 12, 2); // max capacity, in the type's capacity_unit

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['production_site_id', 'zone_id']);
            $table->index(['company_id', 'is_active']);
            $table->unique(['production_site_id', 'zone_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_growing_spaces');
    }
};