<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // Sits with the other store-level billing overrides
            // (default_tax_type, default_payment_terms). A retail counter and
            // an online store in the same company rarely share a default, so
            // this belongs per store rather than per company.
            //
            // nullOnDelete: if the method is removed, the store simply falls
            // back to "no default" instead of pointing at a dead row.
            $table->foreignId('default_payment_method_id')
                ->nullable()
                ->after('default_payment_terms')
                ->constrained('payment_methods')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_payment_method_id');
        });
    }
};