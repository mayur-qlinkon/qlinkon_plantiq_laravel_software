<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();

            $table->year('year');
            $table->decimal('allocated', 5, 1)->default(0);
            $table->decimal('used', 5, 1)->default(0);
            $table->decimal('carried_forward', 5, 1)->default(0);
            $table->decimal('adjustment', 5, 1)->default(0);

            $table->timestamps();

            $table->unique(['company_id', 'employee_id', 'leave_type_id', 'year'], 'leave_balances_unique');
            $table->index(['employee_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
