<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('name', 100);
            $table->string('code', 30);
            $table->enum('type', ['earning', 'deduction']);
            $table->text('description')->nullable();

            $table->enum('calculation_type', ['fixed', 'percentage'])->default('fixed');
            $table->string('percentage_of', 30)->nullable();
            $table->decimal('default_amount', 12, 2)->nullable()->default(0);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_statutory')->default(false);
            $table->boolean('appears_on_payslip')->default(true);

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_components');
    }
};
