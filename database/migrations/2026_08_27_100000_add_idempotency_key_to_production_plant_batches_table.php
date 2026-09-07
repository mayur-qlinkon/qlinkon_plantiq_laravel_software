<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_plant_batches', function (Blueprint $table) {
            // Sent by the create form, one value per rendered page. A retried or
            // double-clicked submit carries the key the first submit already
            // used, so the unique index below rejects the second insert.
            //
            // Nullable on purpose: MySQL treats NULLs as distinct in a unique
            // index, so callers that send no key (API, console) are unaffected
            // and simply get no protection.
            $table->char('idempotency_key', 36)->nullable()->after('batch_code');

            $table->unique(['company_id', 'idempotency_key'], 'plant_batches_company_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::table('production_plant_batches', function (Blueprint $table) {
            $table->dropUnique('plant_batches_company_idempotency_unique');
            $table->dropColumn('idempotency_key');
        });
    }
};