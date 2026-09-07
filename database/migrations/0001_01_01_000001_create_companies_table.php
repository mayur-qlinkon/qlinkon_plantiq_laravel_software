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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subdomain')->nullable()->unique();
            $table->string('domain')->nullable()->unique();

            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('logo')->nullable();

            $table->string('gst_number', 50)->nullable();
            $table->string('currency', 10)->default('INR');

            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->foreignId('state_id')->nullable()->constrained('states');
            $table->string('zip_code', 20)->nullable();
            $table->string('country', 100)->default('India');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
