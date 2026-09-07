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
        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();            
            $table->foreignId('crm_lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('type', [
                'note',           // free text note
                'call',           // phone call logged
                'whatsapp',       // WhatsApp message sent
                'email',          // email sent
                'meeting',        // in-person/virtual meeting
                'stage_change',   // auto-logged by Observer
                'lead_created',   // auto-logged on create
                'converted',      // auto-logged on conversion
                'task_completed', // auto-logged when task done
                'score_changed',  // auto-logged on score update
            ])->index();

            $table->text('description');
            $table->json('meta')->nullable();         // flexible: { from_stage: 'New', to_stage: 'Contacted' }
            $table->boolean('is_auto')->default(false); // true = logged by Observer, false = manual

            $table->timestamps();

            $table->index(['crm_lead_id', 'type']);
            $table->index(['crm_lead_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
    }
};
