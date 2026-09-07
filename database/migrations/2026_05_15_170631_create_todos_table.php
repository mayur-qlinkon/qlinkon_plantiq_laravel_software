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
        Schema::create('todos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');

            $table->text('description')->nullable();

            $table->enum('priority', [
                'low',
                'medium',
                'high',
            ])->default('medium');

            $table->enum('status', [
                'pending',
                'completed',
            ])->default('pending');

            $table->date('due_date')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->unsignedSmallInteger('sort_order')
                ->default(0);

            $table->boolean('is_important')
                ->default(false);

            $table->timestamps();

            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'user_id']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todos');
    }
};
