<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * orders.customer_id pointed at users.id, but a customer in this system is
     * a Client — Invoice, Quotation, InvoiceReturn and InvoiceWriteOff all map
     * customer_id to clients.id. Order was the only outlier, so the admin order
     * form (which picks from clients) died on the foreign key at insert time.
     *
     * A User is a login credential; the Client is the customer. Storefront
     * registration already guarantees both exist for every buyer
     * (StorefrontAuthController::linkOrCreateClientProfile, and the IsCustomer
     * middleware refuses a customer with no client), so no attribution is lost.
     */
    public function up(): void
    {
        // Scratch column, dropped at the end of this migration. Rewriting IDs
        // in place would collide: a row already converted to a client ID can
        // match another row's not-yet-converted user ID.
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_client_id')->nullable()->after('customer_id');
        });

        // clients.user_id already links the two, so existing storefront buyers
        // keep their orders. Matching on company_id as well keeps the mapping
        // inside one tenant.
        DB::statement('
            UPDATE orders o
            INNER JOIN clients c
                ON c.user_id = o.customer_id
               AND c.company_id = o.company_id
            SET o.customer_client_id = c.id
            WHERE o.customer_id IS NOT NULL
        ');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('orders_customer_id_foreign');
            $table->dropColumn('customer_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('customer_client_id', 'customer_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('customer_id')
                ->references('id')
                ->on('clients')
                ->nullOnDelete();
        });
    }

    /**
     * Reverses the column back to users.id. Any order whose client had no
     * linked user becomes a guest order — which is what it would have been
     * under the old schema anyway.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_user_id')->nullable()->after('customer_id');
        });

        DB::statement('
            UPDATE orders o
            INNER JOIN clients c
                ON c.id = o.customer_id
               AND c.company_id = o.company_id
            SET o.customer_user_id = c.user_id
            WHERE o.customer_id IS NOT NULL
        ');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('customer_user_id', 'customer_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('customer_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};