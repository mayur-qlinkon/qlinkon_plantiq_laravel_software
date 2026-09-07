<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Roles Table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name', 100);
            $table->string('slug', 100);

            $table->timestamps();

            $table->unique(['company_id', 'slug']);
        });

        // 2. Permissions Table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('module_group')->nullable();

            $table->timestamps();
        });

        // 3. Permission_Role Pivot Table
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->unique(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
