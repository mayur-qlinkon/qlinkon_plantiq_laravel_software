<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_module_access', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('module_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('assigned_at')->nullable();

            $table->timestamps();

            $table->unique([
                'company_id',
                'user_id',
                'module_id'
            ]);

            $table->index([
                'company_id',
                'module_id'
            ]);

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_module_access');
    }
};