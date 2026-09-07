<?php

namespace App\Services\Platform;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Plan;
use App\Models\CompanyModuleLicense;
use App\Models\Module;
use App\Models\UserModuleAccess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class CompanySubscriptionService
{
    public function getIndexData(): array
    {
        return [
            'subscriptions' => CompanySubscription::with(['company', 'plan'])->latest()->get(),
            'companies' => Company::orderBy('name')->get(),
            'plans' => Plan::where('is_active', true)->orderBy('price')->get(),
        ];
    }

    public function assignSubscription(array $data): CompanySubscription
    {
        $data['is_active'] = $data['is_active'] ?? false;

        $subscription = CompanySubscription::updateOrCreate(
            ['company_id' => $data['company_id']],
            [
                'plan_id' => $data['plan_id'],
                'starts_at' => $data['starts_at'] ?? now(),
                'expires_at' => $data['expires_at'] ?? null,
                'is_active' => $data['is_active'],
            ]
        );

        // ROOT FIX: CheckSubscription middleware caches the active/expired
        // verdict for 5 minutes per company. Without this, a super-admin
        // expiring a subscription right now still leaves that company's
        // dashboard fully open (only module-level checks, which query
        // fresh, reacted) until the stale cache entry naturally expires.
        Cache::forget("company_subscription_active_{$subscription->company_id}");

        $this->ensureLicensesForCompany($data['company_id']);

        return $subscription;
    }

    /**
     * Renew/extend a subscription from now, based on the plan's billing cycle.
     */
    public function renew(CompanySubscription $subscription): CompanySubscription
    {
        $cycle = $subscription->plan->billing_cycle ?? 'monthly';

        $expiresAt = match ($cycle) {
            'yearly'   => now()->addYear(),
            'lifetime' => null,
            default    => now()->addMonth(),
        };

        $subscription->update([
            'starts_at'  => now(),
            'expires_at' => $expiresAt,
            'is_active'  => true,
        ]);

        // ROOT FIX: same stale-cache issue as assignSubscription() — a
        // successful renewal must clear the gate immediately, not up to
        // 5 minutes later, otherwise the user stays stuck on the
        // "subscription expired" redirect right after paying.
        Cache::forget("company_subscription_active_{$subscription->company_id}");

        return $subscription;
    }
   /**
     * No-op — kept only so existing callers don't break.
     *
     * Module licensing is now driven ENTIRELY by syncModuleSeatLimits()
     * from the "Assigned Modules" form. There is no "unlimited seats"
     * concept anymore — auto-creating a seat_limit=NULL row for every
     * plan module (the old behavior here) was the root cause of every
     * module showing up as licensed even when the super admin never
     * typed in a seat count. Do not resurrect that loop.
     */
    public function ensureLicensesForCompany(int $companyId): int
    {
        return 0;
    }

    /**
     * Full declarative reconciliation of a company's module seat licenses —
     * the single source of truth for the "Assigned Modules" form. Call this
     * on every onboard create/update save with the full current form state.
     *
     * Rule (no exceptions, no "unlimited" fallback):
     *   - Module checked AND seats filled  → license row created/updated
     *     with that exact seat_limit.
     *   - Module checked but seats BLANK   → no license row. (Super admin
     *     never grants unlimited seats — blank means "not licensed yet".)
     *   - Module UNCHECKED (any seats value) → license row removed if it
     *     exists, regardless of what's sitting in the seats input.
     *
     * Removing a license also removes every user_module_access row for
     * that company+module, so stale seat usage can't silently reappear
     * if the module is re-licensed later without anyone re-assigning users.
     *
     * @param  array<int>       $selectedModuleIds  Checked module IDs ($data['modules']).
     * @param  array<int,mixed> $moduleSeats        module_id => seat count or blank ($data['module_seats']).
     */
    public function syncModuleSeatLimits(int $companyId, array $selectedModuleIds, array $moduleSeats): void
    {
        $selectedModuleIds = array_map('intval', $selectedModuleIds);

        $this->guardModuleDependencies($selectedModuleIds);

        // Iterating $moduleSeats alone could never revoke anything: the seat
        // input is disabled while its module is unchecked, and a disabled
        // input is not submitted, so an unchecked module never appeared in
        // this array and its licence row survived every save.
        //
        // Reconcile over the union instead — what was selected, what carried a
        // seat value, and what the company already holds a licence for.
        $moduleIds = array_unique(array_merge(
            $selectedModuleIds,
            array_map('intval', array_keys($moduleSeats)),
            CompanyModuleLicense::where('company_id', $companyId)->pluck('module_id')->all()
        ));

        foreach ($moduleIds as $moduleId) {
            $moduleId   = (int) $moduleId;
            $seatLimit  = $moduleSeats[$moduleId] ?? null;
            $isSelected = in_array($moduleId, $selectedModuleIds, true);

            $existing = CompanyModuleLicense::where('company_id', $companyId)
                ->where('module_id', $moduleId)
                ->first();

            // Unchecked, or checked-but-blank — this module must have no license row.
            if (! $isSelected || blank($seatLimit)) {
                if ($existing) {
                    UserModuleAccess::where('company_id', $companyId)
                        ->where('module_id', $moduleId)
                        ->delete();

                    $existing->delete();
                }
                continue;
            }

            if ($existing) {
                $existing->update(['seat_limit' => (int) $seatLimit]);
            } else {
                CompanyModuleLicense::create([
                    'company_id' => $companyId,
                    'module_id'  => $moduleId,
                    'seat_limit' => (int) $seatLimit,
                    'is_active'  => true,
                    'starts_at'  => now(),
                ]);
            }
        }
    }

    /**
     * Some modules cannot work alone: Production stores its worker
     * assignments against HRM employees, so licensing it without HRM
     * produces a module the tenant can open but never use.
     *
     * @param  array<int>  $selectedModuleIds
     * @throws \RuntimeException when a required module is missing.
     */
    private function guardModuleDependencies(array $selectedModuleIds): void
    {
        if (empty($selectedModuleIds)) {
            return;
        }

        $selected = Module::whereIn('id', $selectedModuleIds)->get();
        $selectedSlugs = $selected->pluck('slug')->all();

        foreach ($selected as $module) {
            foreach ($module->depends_on ?? [] as $requiredSlug) {
                if (in_array($requiredSlug, $selectedSlugs, true)) {
                    continue;
                }

                $required = Module::where('slug', $requiredSlug)->first();

                throw new \RuntimeException(
                    "The {$module->name} module also requires the "
                    .($required->name ?? $requiredSlug).' module to be licensed.'
                );
            }
        }
    }
}
