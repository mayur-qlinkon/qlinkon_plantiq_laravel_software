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
        Schema::create('crm_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crm_pipeline_id')->constrained()->cascadeOnDelete();
            $table->string('name');                          // New, Contacted, Qualified, Proposal, Won, Lost
            $table->string('color', 20)->default('#6b7280'); // badge color
            $table->boolean('is_won')->default(false);        // marks conversion
            $table->boolean('is_lost')->default(false);       // marks dead lead
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['crm_pipeline_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_stages');
    }
};
