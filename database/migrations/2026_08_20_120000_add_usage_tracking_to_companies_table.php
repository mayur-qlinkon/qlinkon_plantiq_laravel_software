<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Usage health, so the platform can tell a thriving tenant from one that
 * stopped logging in three weeks ago and needs a phone call.
 *
 * Columns on companies rather than a usage table: this is a snapshot, one row
 * per company, rewritten nightly. History would need its own table, and the
 * question being answered here — "who do I call today?" — does not need it.
 *
 * last_active_at and usage_health are real indexed columns because the tenant
 * list sorts and filters on them. Everything else is display-only and lives in
 * the JSON blob rather than widening the table for figures nobody queries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // The most recent sign of life from anyone in this company.
            $table->timestamp('last_active_at')->nullable()->after('is_active')->index();

            // healthy | normal | slipping | dormant | never_started
            $table->string('usage_health', 20)->nullable()->after('last_active_at')->index();

            // How many distinct days in the last 30 saw activity. 0–30.
            $table->unsignedTinyInteger('active_days_30')->default(0)->after('usage_health');

            // Counts and the 30-day bitmap behind the sparkline. Display only.
            $table->json('usage_snapshot')->nullable()->after('active_days_30');

            // When the nightly job last wrote the three columns above. A stale
            // value here means the cron stopped, which otherwise looks
            // identical to every tenant going quiet at once.
            $table->timestamp('usage_computed_at')->nullable()->after('usage_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['last_active_at']);
            $table->dropIndex(['usage_health']);
            $table->dropColumn([
                'last_active_at',
                'usage_health',
                'active_days_30',
                'usage_snapshot',
                'usage_computed_at',
            ]);
        });
    }
};