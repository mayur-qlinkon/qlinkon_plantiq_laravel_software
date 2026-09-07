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
        Schema::create('crm_lead_tags', function (Blueprint $table) {
            $table->foreignId('crm_lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crm_tag_id')->constrained('crm_tags')->cascadeOnDelete();
            $table->primary(['crm_lead_id', 'crm_tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_lead_tags');
    }
};
