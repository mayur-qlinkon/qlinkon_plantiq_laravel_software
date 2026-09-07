<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name');
            // --- Future-Safe Additions ---
            $table->string('short_name', 50)->nullable()->comment('e.g., kg, pcs, ltr');
            $table->boolean('is_active')->default(true)->comment('Toggle visibility without deleting');
            $table->timestamps(); // Creates both created_at and updated_at
            $table->softDeletes(); // Creates deleted_at for safe deletions
            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'short_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
