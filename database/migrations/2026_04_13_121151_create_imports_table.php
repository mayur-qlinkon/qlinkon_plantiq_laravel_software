<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50)->index(); // categories, units, products, skus
            $table->string('file_path');
            $table->string('temp_path')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('success_rows')->default(0);
            $table->unsignedInteger('created_rows')->default(0);
            $table->unsignedInteger('updated_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->unsignedInteger('limit_skipped_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->boolean('is_dry_run')->default(false);
            $table->enum('import_mode', ['create_only', 'update_only', 'create_or_update'])
                ->default('create_or_update');
            $table->enum('duplicate_mode', ['skip', 'update', 'error'])
                ->default('skip');
            $table->json('duplicate_meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
