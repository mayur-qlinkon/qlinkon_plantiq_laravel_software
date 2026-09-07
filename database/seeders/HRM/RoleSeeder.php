<?php

namespace Database\Seeders\HRM;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 🌟 Dynamically grab the company ID injected by the Visual Seeder Platform with an isset fallback
        $requestAttributes = request() ? request()->attributes->all() : [];
        $companyId = isset($requestAttributes['seeder_company_id']) ? $requestAttributes['seeder_company_id'] : 1;

        // 🚨 Fetch only CRM related permissions to map slugs to IDs (eager load)
        $permissions = Permission::whereIn('slug', [
            'crm_dashboard.view',
            'crm_leads.view',
            'crm_leads.create',
            'crm_leads.update',
            'crm_leads.change_stage',
            'crm_tasks.view',
            'crm_tasks.create',
            'crm_tasks.complete',
            'crm_sources.view',
            'crm_tags.view',
        ])->pluck('id', 'slug');

        // ────────────────────────────────────────────────────────────────────
        // CREATE STRIP-DOWN EMPLOYEE ROLE ONLY (No Company creation, No User footprints)
        // ────────────────────────────────────────────────────────────────────
        DB::transaction(function () use ($companyId, $permissions) {
            
            // Create or find role for this specific company context safely
            $role = Role::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'slug'       => 'employee',
                ],
                [
                    'name'       => 'Employee',
                ]
            );

            // Extract valid permission IDs available in this platform database instance
            $permissionIds = collect($permissions)
                ->filter()
                ->values()
                ->toArray();

            // Sync the structured permissions matrix to this role instance
            $role->permissions()->sync($permissionIds);

            if (isset($this->command)) {
                $this->command->info("Employee Role synced with " . count($permissionIds) . " permissions for Company ID: {$companyId}.");
            }
        });

        if (isset($this->command)) {
            $this->command->info('✅ Employee Role seeding completed successfully.');
        }
    }
}