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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->nullable()                
                ->constrained()
                ->nullOnDelete();                        

            // ── 1. Core Identity ──
            $table->string('name'); // e.g., "Basic", "Premium"
            $table->string('slug')->unique();
            $table->string('description')->nullable(); // Optional subtitle under the name

            // ── 2. Pricing & Billing ──
            $table->decimal('price', 10, 2)->default(0); // e.g., 9.99
            $table->enum('billing_cycle', ['monthly', 'yearly', 'lifetime'])->default('monthly'); // Used for the "/mo" text
            $table->integer('trial_days')->nullable(); // E.g., 14 for the "Free trial" plan

            // ── 3. Resource Limits (For Tenant Gates) ──
            $table->integer('user_limit')->default(1);
            $table->integer('store_limit')->default(1);
            $table->unsignedInteger('product_limit')->default(50);
            $table->unsignedInteger('employee_limit')->default(50);
            $table->integer('ocr_scan_limit')->default(0);
            $table->integer('ai_chat_daily_limit')
                ->default(0);
            $table->integer('ai_token_daily_limit')
                ->default(0);

            // ── 4. UI Customization (For the Frontend Design) ──
            $table->boolean('is_recommended')->default(false); // Triggers the Green Star Ribbon
            $table->string('button_text')->default('Get Started'); // Allows "Start Trial" vs "Get Started"
            $table->string('button_link')->nullable(); // e.g., "mailto:sales@app.com" or "https://wa.me/..."

            // ── 5. System Controls ──
            $table->integer('sort_order')->default(0); // Controls left-to-right order (0, 1, 2)
            $table->boolean('is_active')->default(true); // Hide/Show plan temporarily
            $table->json('builder_selection')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Crucial for SaaS: Never hard-delete plans that companies are currently using
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
