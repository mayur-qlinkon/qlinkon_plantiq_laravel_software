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
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();            

            // ── Pipeline & Stage ──
            $table->foreignId('crm_pipeline_id')->constrained()->restrictOnDelete();
            $table->foreignId('crm_stage_id')->constrained()->restrictOnDelete();

            // ── Source ──
            $table->foreignId('crm_lead_source_id')->nullable()->constrained()->nullOnDelete();

            // ── Person ──
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable()->index();
            $table->string('company_name')->nullable();

            // ── Address ──
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->default('India');
            $table->string('zip_code', 20)->nullable();

            // ── Social ──
            $table->string('instagram_id')->nullable();
            $table->string('facebook_id')->nullable();
            $table->string('google_profile')->nullable();
            $table->string('website')->nullable();

            // ── Scoring & Priority ──
            $table->unsignedInteger('score')->default(0);     // auto-calculated
            $table->enum('priority', ['low', 'medium', 'high', 'hot'])->default('medium');
            $table->decimal('lead_value', 15, 2)->nullable();  // estimated deal value

            // ── Conversion ──
            $table->foreignId('client_id')                     // linked after convert
                ->nullable()
                ->constrained('clients')
                ->nullOnDelete();
            $table->boolean('is_converted')->default(false);
            $table->timestamp('converted_at')->nullable();

            // ── CRM Links ──
            // Links storefront order that auto-created this lead
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();

            // ── Contact Tracking ──
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('next_followup_at')->nullable();
            $table->string('followup_label', 100)->nullable();
            $table->text('followup_note')->nullable();
            $table->timestamp('followup_reminded_at')->nullable();

            // ── Notes ──
            $table->text('description')->nullable();

            // ── Audit ──
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'crm_stage_id']);
            $table->index(['company_id', 'is_converted']);
            $table->index(['company_id', 'next_followup_at']);
            $table->index(['company_id', 'score']);
            $table->index(['phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_leads');
    }
};
