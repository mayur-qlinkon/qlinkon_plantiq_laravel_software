<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A project is a unit of WORK. It deliberately carries no money columns —
 * a project's value is the sum of its charges, never its own field. This is
 * what stops payment activity from silently mutating delivery status the way
 * the legacy module did.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // NOT NULL to match payments.store_id, so a project and the money
            // against it can never disagree about which store they belong to.
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // draft, active, on_hold, completed, cancelled
            // Set by a human. Never derived from payments.
            $table->string('status', 30)->default('draft');

            $table->date('start_date')->nullable();
            $table->date('expected_end_date')->nullable();
            $table->date('completed_at')->nullable();

            // Who is handling this internally.
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            // Optional cross-module tracking references.
            //
            // Deliberately plain integers with NO foreign key constraint: the
            // Projects module is standalone and licensed separately, so a tenant
            // may run it without Invoicing or Quotations enabled. A database
            // level constraint would couple the modules; these are lookup hints
            // only, resolved in application code when the module is available.
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'store_id']);
            $table->index(['company_id', 'client_id']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'start_date']);
            $table->index('quotation_id');
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};