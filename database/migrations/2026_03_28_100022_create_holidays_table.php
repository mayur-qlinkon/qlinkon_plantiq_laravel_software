<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('name', 150);
            $table->date('date');
            $table->date('end_date')->nullable();
            $table->enum('type', ['national', 'state', 'company', 'restricted', 'optional'])->default('company');
            $table->text('description')->nullable();

            $table->boolean('is_paid')->default(true);
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_active')->default(true);

            $table->json('applicable_departments')->nullable();
            $table->json('applicable_stores')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'name', 'date']);
            $table->index(['company_id', 'date']);
            $table->index(['company_id', 'type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
