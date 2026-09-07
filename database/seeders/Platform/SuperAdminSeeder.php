<?php

namespace Database\Seeders\Platform;

use App\Models\Role;
use App\Models\User;
use App\Enums\Auth\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the platform super admin user.
     *
     * Super admins are NOT tied to any tenant company (company_id = null).
     * This means:
     *  - The Tenantable trait never scopes their queries by company.
     *  - Deleting any company can never remove the super admin account.
     *  - Subscription and module-access middleware auto-bypass for null company_id.
     *
     * The super_admin Role is also company-less (company_id = null) — a platform-level role.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // ── 1. Create the platform-level super_admin role (no company) ──
            $role = Role::firstOrCreate(
                ['slug' => 'super_admin', 'company_id' => null],
                ['name' => 'Super Admin']
            );

            // ── 2. Create / update the super admin user ──
            $adminName = env('SUPER_ADMIN_NAME', 'Super Admin');
            $adminEmail = env('SUPER_ADMIN_EMAIL', 'superadmin@example.com');
            $adminPassword = env('SUPER_ADMIN_PASSWORD', 'password');

            $user = User::withTrashed()->firstOrCreate(
                ['email' => $adminEmail],
                [
                    'company_id' => null,   // ← NOT tied to any company
                    'user_type' => UserType::SUPER_ADMIN,
                    'name' => $adminName,
                    'password' => Hash::make($adminPassword),
                    'status' => 'active',
                ]
            );

            // Always keep these fields current — even if the user already existed.
            $user->restore();   // un-soft-delete if somehow soft-deleted
            $user->update([
                'name' => $adminName,
                'company_id' => null,       // ← Detach from any company
                'user_type' => UserType::SUPER_ADMIN,
                'password' => Hash::make($adminPassword),
                'status' => 'active',
            ]);

            $user->roles()->syncWithoutDetaching([$role->id]);

            $this->command->info("✅ Super Admin ready: {$user->name} ({$user->email}) — company_id = NULL");
        });
    }
}
