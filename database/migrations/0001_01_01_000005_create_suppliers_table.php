<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // --- 👤 Basic Contact Details ---
            $table->string('name', 150);
            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('pincode', 10)->nullable();

            // 🌟 THE ROOT FIX: Link to our master States table
            $table->foreignId('state_id')->nullable()->constrained('states');

            // --- 🇮🇳 Indian Compliance ---
            $table->string('gstin', 15)->nullable()->unique();
            $table->string('pan', 10)->nullable();

            // Registration Types: regular, composition, unregistered, sez, overseas
            $table->string('registration_type')->default('regular');

            // --- 🏦 Banking (Essential for B2B Payments) ---
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->string('branch')->nullable();

            // --- 💰 Financials & Credit Control ---
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->enum('balance_type', ['payable', 'advance'])->default('payable');

            // 🔥 Pro-Tip: current_balance allows instant "Who do I owe money?" reports
            // without calculating 10,000 ledger rows every time.
            $table->decimal('current_balance', 15, 2)->default(0);

            $table->unsignedSmallInteger('credit_days')->default(0);
            $table->decimal('credit_limit', 15, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
