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
        Schema::create('crm_lead_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(true);     // switch: true = only 1 primary
            $table->timestamps();

            $table->unique(['crm_lead_id', 'user_id']);
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_lead_assignees');
    }
};
