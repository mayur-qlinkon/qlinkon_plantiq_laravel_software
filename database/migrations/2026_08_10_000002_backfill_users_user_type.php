<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fresh installs never had the legacy flags — nothing to backfill.
        if (! Schema::hasColumn('users', 'is_super_admin')) {
            return;
        }

        // Order matters. The most privileged flag wins, so a row carrying more
        // than one legacy flag can never silently downgrade to a weaker identity.
        DB::table('users')
            ->where('is_super_admin', 1)
            ->update(['user_type' => 'super_admin']);

        DB::table('users')
            ->where('is_super_admin', 0)
            ->where('is_customer', 1)
            ->update(['user_type' => 'customer']);

        DB::table('users')
            ->where('is_super_admin', 0)
            ->where('is_customer', 0)
            ->where('is_company_admin', 1)
            ->update(['user_type' => 'company_admin']);

        // Everything left (old 'full' and 'employee') becomes a team member.
        DB::table('users')
            ->where('is_super_admin', 0)
            ->where('is_customer', 0)
            ->where('is_company_admin', 0)
            ->update(['user_type' => 'internal']);

        // Safety net: a storefront user with a client profile but no legacy
        // is_customer flag would be locked out of the customer login entirely.
        DB::table('users')
            ->where('user_type', 'internal')
            ->whereIn('id', function ($query) {
                $query->select('user_id')
                    ->from('clients')
                    ->whereNotNull('user_id')
                    ->whereNull('deleted_at');
            })
            ->update(['user_type' => 'customer']);
    }

    public function down(): void
    {
        // Legacy flags are still present at this point, so the backfill is
        // simply re-runnable. No destructive rollback needed.
    }
};