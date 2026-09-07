<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The company's service catalog — an internal price list, nothing more.
 *
 * Holds no client data and is not financial truth. Whatever is sold gets
 * snapshotted onto project_client_services and project_charges, so repricing
 * here can never rewrite what was already billed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_services', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('name');

            // hosting, domain, ssl, maintenance, seo, other
            $table->string('service_type', 50)->default('other');

            // one_time, monthly, quarterly, half_yearly, yearly, custom
            //
            // No is_renewable flag: anything that is not one_time renews by
            // definition. The legacy module kept that truth in two places, they
            // drifted, and renew buttons silently stopped working.
            $table->string('billing_cycle', 50)->default('one_time');

            // Only meaningful when billing_cycle is custom.
            $table->unsignedInteger('duration_days')->nullable();

            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(18);

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Name is the identity here — this is an internal list picked from a
            // dropdown, never a public URL, so no slug is needed.
            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_services');
    }
};