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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();
            //  $table->unsignedBigInteger('store_id')
            //     ->default(0)                
            //     ->comment('0 = company-level setting, otherwise stores.id');

            $table->string('key');
            $table->text('value')->nullable();

            $table->string('group')->nullable();
            // general, branding, billing, seo, social, pages

            $table->string('type')->nullable();
            // text, image, json, number, boolean

            $table->timestamps();

            $table->unique(['company_id', 'key']);
            // $table->unique(['company_id', 'store_id', 'key'], 'settings_company_store_key_unique');
            // $table->index(['company_id', 'store_id'], 'settings_company_store_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
