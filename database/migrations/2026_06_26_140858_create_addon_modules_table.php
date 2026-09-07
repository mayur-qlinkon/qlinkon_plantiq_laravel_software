<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addon_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('addon_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('module_id')
                ->constrained()
                ->cascadeOnDelete();
            // Future:
            // Website addon
            // -> Blog module mandatory
            // -> AI module optional
            $table->boolean('is_required')->default(true);

            $table->unique([
                'addon_id',
                'module_id'
            ]);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addon_modules');
    }
};