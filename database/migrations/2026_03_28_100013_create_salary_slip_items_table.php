<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_slip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_slip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_component_id')->nullable()->constrained()->nullOnDelete();

            $table->string('component_name', 100);
            $table->string('component_code', 30);
            $table->enum('type', ['earning', 'deduction']);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('calculation_detail', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['salary_slip_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_slip_items');
    }
};
