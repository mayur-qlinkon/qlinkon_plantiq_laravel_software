<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

class AssignEmployeeRole extends Command
{
    protected $signature   = 'employees:assign-role {--company_id= : Specific company ID (optional)}';
    protected $description = 'Assign the employee role to all existing employee-type users who do not have it yet';

    public function handle(): void
    {
        $query = User::internal()->whereHas('employee');

        if ($this->option('company_id')) {
            $query->where('company_id', $this->option('company_id'));
        }

        $employees = $query->with('roles')->get();

        if ($employees->isEmpty()) {
            $this->info('No employee-type users found.');
            return;
        }

        $assigned = 0;
        $skipped  = 0;

        foreach ($employees as $user) {
            // Find the employee role for this user's company
            $role = Role::where('slug', 'employee')
                ->where('company_id', $user->company_id)
                ->first();

            if (! $role) {
                $this->warn("⚠️  Employee role not found for company_id: {$user->company_id} (User: {$user->name}). Run RoleSeeder first.");
                $skipped++;
                continue;
            }

            // Skip if already has the role
            if ($user->roles->contains('id', $role->id)) {
                $skipped++;
                continue;
            }

            $user->roles()->attach($role->id);
            $assigned++;
            $this->line("✅ Assigned employee role to: {$user->name} (ID: {$user->id})");
        }

        $this->info("Done. Assigned: {$assigned}, Skipped/Already had role: {$skipped}");
    }
}