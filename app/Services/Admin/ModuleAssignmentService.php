<?php

namespace App\Services\Admin;

use App\Models\CompanyModuleLicense;
use App\Models\Module;
use App\Models\User;
use App\Models\UserModuleAccess;
use Illuminate\Support\Facades\DB;

class ModuleAssignmentService
{
    /**
     * Licenses currently held by a company, keyed by module_id.
     * One query, reused by seat-usage widgets and the assign-modules form.
     */
    public function licensesForCompany(int $companyId)
    {
        return CompanyModuleLicense::with('module')
            ->where('company_id', $companyId)
            ->get()
            ->keyBy('module_id');
    }

    /**
     * Same as licensesForCompany(), but holds a row lock for the transaction.
     *
     * Only for the assignment path, where the seat count must not move between
     * the check and the insert. Read-only callers (widgets, forms) should keep
     * using licensesForCompany() so they never block a write.
     *
     * The eager load is a second query on purpose: `with()` under
     * lockForUpdate would extend the lock to the modules table, which is
     * platform-level and shared by every tenant.
     */
    private function licensesForCompanyLocked(int $companyId)
    {
        $licenses = CompanyModuleLicense::where('company_id', $companyId)
            ->lockForUpdate()
            ->get()
            ->keyBy('module_id');

        $licenses->load('module');

        return $licenses;
    }

    /**
     * Seats used for one license (live count — no denormalized drift).
     */
    public function seatsUsed(int $companyId, int $moduleId): int
    {
        return UserModuleAccess::where('company_id', $companyId)
            ->where('module_id', $moduleId)
            ->count();
    }

    /**
     * Seats used for EVERY module in one company, in a single query.
     * Returns [module_id => used_count]. Use this on any page that renders
     * a module list (Users create/edit, Users index) — avoids N+1 per-module
     * COUNT queries.
     */
    public function seatsUsedForCompany(int $companyId): array
    {
        return UserModuleAccess::where('company_id', $companyId)
            ->selectRaw('module_id, COUNT(*) as used')
            ->groupBy('module_id')
            ->pluck('used', 'module_id')
            ->all();
    }

    /**
     * Sync a user's module assignments to exactly the given module IDs.
     * Used by both the Create and Edit user forms — same checkbox list.
     *
     * The owner is assigned seats like everyone else. Granting free access
     * would let one purchased licence serve both the owner and a team member.
     *
     * @param  array<int>  $moduleIds  Module IDs the form submitted as checked.
     * @throws \RuntimeException when a seat limit would be exceeded.
     */
    public function syncForUser(User $user, array $moduleIds, ?User $assignedBy = null): void
    {
        $companyId = $user->company_id;
        $moduleIds = array_values(array_unique(array_map('intval', $moduleIds)));

        DB::transaction(function () use ($user, $companyId, $moduleIds, $assignedBy) {
            // Locked, not just loaded.
            //
            // The seat check below is read-then-write: count the seats, compare
            // to the limit, then insert. Two admins assigning the last seat at
            // the same moment both read the same count, both pass, and the
            // company ends up over its licence — a transaction alone does not
            // prevent that, because nothing here conflicts until the inserts
            // land, and by then both checks have already succeeded.
            //
            // Locking the licence rows makes them the serialisation point: the
            // second request waits here until the first commits, then counts
            // seats that already include the first insert.
            $licenses = $this->licensesForCompanyLocked($companyId);

            $currentModuleIds = UserModuleAccess::where('company_id', $companyId)
                ->where('user_id', $user->id)
                ->pluck('module_id')
                ->all();

            $toAdd = array_diff($moduleIds, $currentModuleIds);
            $toRemove = array_diff($currentModuleIds, $moduleIds);

            // Validate seat availability for every module being newly added.
            // Never trust the frontend's disabled checkbox — re-check here.
            foreach ($toAdd as $moduleId) {
                $license = $licenses->get($moduleId);

                // No license row = the company has not purchased this module.
                // Mirror user_can_access_module(): deny, never silently allow.
                if (! $license) {
                    throw new \RuntimeException(
                        'That module is not licensed for your company.'
                    );
                }

                if (! $license->isCurrentlyValid()) {
                    throw new \RuntimeException(
                        "The {$license->module->name} module license is not currently active for your company."
                    );
                }

                $used = $this->seatsUsed($companyId, $moduleId);

                if (! is_null($license->seat_limit) && $used >= $license->seat_limit) {
                    throw new \RuntimeException(
                        "Seat limit reached for the {$license->module->name} module ({$license->seat_limit} seats)."
                    );
                }
            }

            if (! empty($toRemove)) {
                UserModuleAccess::where('company_id', $companyId)
                    ->where('user_id', $user->id)
                    ->whereIn('module_id', $toRemove)
                    ->delete();
            }

            foreach ($toAdd as $moduleId) {
                UserModuleAccess::create([
                    'company_id'  => $companyId,
                    'user_id'     => $user->id,
                    'module_id'   => $moduleId,
                    'assigned_by' => $assignedBy?->id,
                    'assigned_at' => now(),
                ]);
            }
        });
    }

    /**
     * Modules the company can license-assign (i.e. also present in the
     * subscribed plan). Used to build the checkbox list on the user form —
     * only show modules the company actually purchased.
     */
    public function assignableModulesForCompany(int $companyId)
    {
        // 🌟 ROOT FIX: only show modules the company actually holds a
        // currently-valid license for — not every module in the plan.
        // Mirrors RoleController::getLicensedGroupedPermissions().
        return CompanyModuleLicense::with('module')
            ->where('company_id', $companyId)
            ->get()
            ->filter(fn ($license) => $license->isCurrentlyValid())
            ->pluck('module')
            ->filter()
            ->unique('id')
            ->values();
    }
}