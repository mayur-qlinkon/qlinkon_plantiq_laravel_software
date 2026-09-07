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
        Schema::create('production_zones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            // Denormalized root reference, kept on every zone regardless of
            // tree depth, so "all zones under this site" never needs a
            // recursive query.
            $table->foreignId('production_site_id')
                ->constrained()
                ->cascadeOnDelete();

            // Self-referencing adjacency list — arbitrary depth
            // (Site -> Block -> Row -> Bench -> Shelf, or Site -> Bed direct).
            // cascadeOnDelete so a force-delete matches the soft-delete
            // cascade in App\Models\Production\Zone::booted() — a subtree
            // is destroyed together either way, never silently orphaned.
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('production_zones')
                ->cascadeOnDelete();

            $table->string('name'); // tenant-labeled: Block, Row, Bench, Shelf...
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['production_site_id', 'parent_id']);
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_zones');
    }
};