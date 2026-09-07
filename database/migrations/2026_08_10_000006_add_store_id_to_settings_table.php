<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Settings become store-aware.
     *
     * store_id is NOT NULL with a 0 sentinel rather than a nullable foreign
     * key on purpose: MySQL ignores NULLs in unique indexes, so a nullable
     * column would silently allow duplicate company-level rows for the same
     * key. The trade-off is that no FK constraint can be declared, since 0
     * is not a real store id.
     *
     *   store_id = 0   → company-level  (branding, notifications, system)
     *   store_id = N   → belongs to store N (billing, bank, store SEO)
     */
    public function up(): void
    {
        if (! Schema::hasColumn('settings', 'store_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->unsignedBigInteger('store_id')
                    ->default(0)
                    ->after('company_id')
                    ->comment('0 = company-level setting, otherwise stores.id');
            });
        }

        // Order matters. The company_id foreign key leans on the old unique
        // index as its supporting index, so MySQL refuses to drop it while it
        // is the only index starting with company_id. Creating the new index
        // first gives the constraint somewhere else to point.
        Schema::table('settings', function (Blueprint $table) {
            $table->index(['company_id', 'store_id'], 'settings_company_store_index');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_company_id_key_unique');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->unique(['company_id', 'store_id', 'key'], 'settings_company_store_key_unique');
        });
    }

    public function down(): void
    {
        // Rows for real stores would collide on the old two-column index.
        DB::table('settings')->where('store_id', '>', 0)->delete();

        Schema::table('settings', function (Blueprint $table) {
            $table->unique(['company_id', 'key'], 'settings_company_id_key_unique');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_company_store_key_unique');
            $table->dropIndex('settings_company_store_index');
            $table->dropColumn('store_id');
        });
    }
};