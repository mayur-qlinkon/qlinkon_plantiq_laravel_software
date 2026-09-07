<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();

            // THE IRON WALL
            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            // Links the warehouse to a specific store
            $table->foreignId('store_id');

            // Core Info
            $table->string('name')->comment('e.g., Main Godown, West Branch');
            $table->string('code')->nullable()->comment('WH-001 etc');
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();

            // Location
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->foreignId('state_id')->nullable()->constrained('states');
            $table->string('zip_code', 20)->nullable();
            $table->string('country', 100)->default('India');

            // Configuration & Status
            $table->boolean('is_default')->default(false)->comment('If true, auto-route orders here first');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
