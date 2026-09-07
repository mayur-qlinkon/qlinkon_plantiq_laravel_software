<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hrm_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('hrm_task_comments')->cascadeOnDelete();

            $table->text('body');
            $table->boolean('is_system')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['hrm_task_id', 'created_at']);
            $table->index(['parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_task_comments');
    }
};
