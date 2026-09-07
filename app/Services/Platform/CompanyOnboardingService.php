<?php

namespace App\Services\Platform;

use App\Enums\Auth\UserType;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * CompanyOnboardingService
 *
 * The one place a tenant account is created, changed or terminated:
 *   Company → Owner User → Default Store → Plan → Subscription → licences
 *
 * This absorbed a thinner service of the same name that built Company, Owner
 * and Store with line-for-line identical code but no plan or subscription.
 * Two services meant two answers to "how is a company created", and the
 * platform had two screens each calling a different one.
 *
 * Every write runs inside a single transaction. A half-made company is worse
 * than none at all, because nothing on the platform can repair one.
 */
class CompanyOnboardingService
{
    /**
     * Plaintext credentials of the auto-created staff user, for one-time
     * display on the onboarding result screen. Null when none was created.
     * Never persisted — read it immediately after onboard() returns.
     */
    public ?array $defaultUserCredentials = null;

    public function __construct(
        private CompanySubscriptionService $licenseService,
        private TenantBootstrapper $bootstrapper
    ) {}

    // ──────────────────────────────────────────────────────────────
    // CREATE
    // ──────────────────────────────────────────────────────────────

    public function onboard(array $data): Company
    {
        return DB::transaction(function () use ($data) {

            // 1. Company
            $slug = filled($data['slug'] ?? null)
                ? $data['slug']
                : Str::slug($data['company_name']) . '-' . Str::lower(Str::random(5));

            $company = Company::create([
                'name'       => $data['company_name'],
                'slug'       => $slug,
                'subdomain'  => $data['subdomain'] ?? null,
                'domain'     => $data['domain']    ?? null,
                'email'      => $data['company_email'],
                'phone'      => $data['phone']      ?? null,
                'city'       => $data['city']       ?? null,
                'state_id'   => $data['state_id']   ?? null,
                'gst_number' => $data['gst_number'] ?? null,
                'is_active'  => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            ]);

            // 2. Owner user
            $owner = User::create([
                'company_id' => $company->id,
                'name'       => $data['owner_name'],
                'email'      => $data['owner_email'],
                'password'   => Hash::make($data['owner_password']),
                'state_id'   => $data['state_id'] ?? null,
                'status'     => 'active',
                'user_type'  => UserType::COMPANY_ADMIN,
            ]);

            // 2b. Global Owner role — resolved, never duplicated per company.
            $ownerRole = Role::resolveGlobalOwnerRole();
            $owner->roles()->syncWithoutDetaching([$ownerRole->id]);

            // 3. Default store
            $store = Store::create([
                'company_id' => $company->id,
                'name'       => $data['company_name'] . ' - Main Branch',
                'slug'       => Str::slug($data['company_name'] . '-main'),
                'state_id'   => $data['state_id'] ?? null,
                'is_active'  => true,
            ]);
            $owner->stores()->attach($store->id);

            // 3b. Core starter data — role, staff user, client, payment method.
            // Outside the plan block below because the 'core' permission group
            // is granted to every company, plan or no plan.
            $this->defaultUserCredentials = $this->bootstrapper
                ->bootstrapCore($company, $store, $data);

            // 4. Plan — only when plan data was supplied.
            if ($this->hasPlanData($data)) {
                $plan = $this->createPlan($company, $data);

                // 5. Subscription
                $this->createSubscription($company, $plan, $data);

                // 6. Module licences. A plan-included module with no licence
                // row is denied to every non-owner user, so this must run
                // immediately after every subscription create.
                $this->licenseService->syncModuleSeatLimits(
                    $company->id,
                    $data['modules'] ?? [],
                    $data['module_seats'] ?? []
                );

                // 7. Seed starter data for the plan's modules, so the tenant's
                // first visit to each one is usable rather than a blocked
                // empty state.
                $this->bootstrapper->bootstrapModules($company, $store, $data['modules'] ?? []);
            }

            Log::info('[Onboarding] Company created', [
                'company_id' => $company->id,
                'slug'       => $company->slug,
                'has_plan'   => $this->hasPlanData($data),
            ]);

            return $company;
        });
    }

    // ──────────────────────────────────────────────────────────────
    // UPDATE
    // ──────────────────────────────────────────────────────────────

    public function update(Company $company, array $data): Company
    {
        return DB::transaction(function () use ($company, $data) {

            // 1. Company. subdomain honours explicit removal — it is not
            // auto-refilled from the slug or the old value on update.
            $company->update([
                'name'       => $data['company_name'],
                'slug'       => $data['slug'],
                'subdomain'  => $data['subdomain'] ?? null,
                'domain'     => $data['domain']    ?? null,
                'email'      => $data['company_email'],
                'phone'      => $data['phone']      ?? null,
                'city'       => $data['city']       ?? null,
                'state_id'   => $data['state_id']   ?? null,
                'gst_number' => $data['gst_number'] ?? null,
                'is_active'  => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            ]);

            // 2. Plan
            $subscription = $company->subscription()->with('plan')->first();

            if ($subscription && $subscription->plan && $this->hasPlanData($data)) {
                $this->updatePlan($subscription->plan, $data);
            } elseif (! $subscription && $this->hasPlanData($data)) {
                // No subscription yet → create plan + subscription.
                $plan = $this->createPlan($company, $data);
                $this->createSubscription($company, $plan, $data);
                $this->licenseService->syncModuleSeatLimits(
                    $company->id,
                    $data['modules'] ?? [],
                    $data['module_seats'] ?? []
                );

                Log::info('[Onboarding] Company updated — subscription created', [
                    'company_id' => $company->id,
                ]);

                return $company->fresh();
            }

            // 3. Subscription dates / status
            if ($subscription) {
                $subscription->update([
                    'starts_at'  => $data['starts_at']  ?? $subscription->starts_at,
                    'expires_at' => $data['expires_at'] ?? null,
                    'is_active'  => isset($data['sub_is_active'])
                        ? (bool) $data['sub_is_active']
                        : $subscription->is_active,
                ]);

                // Gated on the same signal as the plan sync above. Licence
                // reconciliation deletes rows for anything not selected, so it
                // must never run on a submission that did not carry the module
                // section — an absent key would read as "remove everything".
                if (array_key_exists('modules', $data) && is_array($data['modules'])) {
                    $this->licenseService->syncModuleSeatLimits(
                        $company->id,
                        $data['modules'],
                        $data['module_seats'] ?? []
                    );
                }
            }

            Log::info('[Onboarding] Company updated', ['company_id' => $company->id]);

            return $company->fresh();
        });
    }

    // ──────────────────────────────────────────────────────────────
    // DELETE
    // ──────────────────────────────────────────────────────────────

    /**
     * Terminate a company: soft-deletes its users, its stores and itself.
     *
     * Refuses outright when a super admin belongs to the company. The guard
     * lives here rather than only in the controller because a super admin
     * account is how the platform itself is reached — losing one to a stray
     * delete is unrecoverable, and a guard the caller can forget is no guard.
     *
     * Roles are checked alongside user_type: user_type is the source of truth,
     * but an account still carrying the super_admin role is treated as one too,
     * so a half-migrated record cannot slip through.
     *
     * @throws RuntimeException when the company holds a super admin account.
     */
    public function delete(Company $company): void
    {
        $hasSuperAdmin = $company->users()
            ->where(function ($query) {
                $query->where('user_type', UserType::SUPER_ADMIN)
                    ->orWhereHas('roles', fn ($r) => $r->where('slug', 'super_admin'));
            })
            ->exists();

        if ($hasSuperAdmin) {
            throw new RuntimeException(
                "Cannot delete \"{$company->name}\" — it contains a platform super admin account."
            );
        }

        DB::transaction(function () use ($company) {
            // Only regular users. The guard above already established that this
            // company holds no super admin, but the condition stays as a second
            // line of defence rather than a trusted assumption.
            $company->users()
                ->where('user_type', '!=', UserType::SUPER_ADMIN)
                ->delete();

            $company->stores()->delete();
            $company->delete();

            Log::info('[Onboarding] Company terminated', [
                'company_id' => $company->id,
                'slug'       => $company->slug,
            ]);
        });
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────

    private function hasPlanData(array $data): bool
    {
        return filled($data['plan_name'] ?? null);
    }

    private function createPlan(Company $company, array $data): Plan
    {
        $plan = Plan::create([
            'company_id'           => null,
            'name'                 => $data['plan_name'],
            'slug'                 => Str::slug($data['plan_name']) . '-' . Str::lower(Str::random(4)),
            'description'          => $data['plan_description']     ?? null,
            'price'                => $data['plan_price']           ?? 0,
            'billing_cycle'        => $data['billing_cycle']        ?? 'monthly',
            'trial_days'           => $data['trial_days']           ?? 0,
            'user_limit'           => $data['user_limit']           ?? 1,
            'store_limit'          => $data['store_limit']          ?? 1,
            'product_limit'        => $data['product_limit']        ?? 50,
            'employee_limit'       => $data['employee_limit']       ?? 50,
            'ocr_scan_limit'       => $data['ocr_scan_limit']       ?? 50,
            'ai_chat_daily_limit'  => $data['ai_chat_daily_limit']  ?? 50,
            'ai_token_daily_limit' => $data['ai_token_daily_limit'] ?? 5000,
            'is_active'            => true,
            'is_recommended'       => false,
        ]);

        if (! empty($data['modules']) && is_array($data['modules'])) {
            $plan->modules()->sync($data['modules']);
        }

        return $plan;
    }

    private function updatePlan(Plan $plan, array $data): Plan
    {
        $plan->update([
            'name'                 => $data['plan_name'],
            'description'          => $data['plan_description']     ?? null,
            'price'                => $data['plan_price']           ?? $plan->price,
            'billing_cycle'        => $data['billing_cycle']        ?? $plan->billing_cycle,
            'trial_days'           => $data['trial_days']           ?? $plan->trial_days,
            'user_limit'           => $data['user_limit']           ?? $plan->user_limit,
            'store_limit'          => $data['store_limit']          ?? $plan->store_limit,
            'product_limit'        => $data['product_limit']        ?? $plan->product_limit,
            'employee_limit'       => $data['employee_limit']       ?? $plan->employee_limit,
            'ocr_scan_limit'       => $data['ocr_scan_limit']       ?? $plan->ocr_scan_limit,
            'ai_chat_daily_limit'  => $data['ai_chat_daily_limit']  ?? $plan->ai_chat_daily_limit,
            'ai_token_daily_limit' => $data['ai_token_daily_limit'] ?? $plan->ai_token_daily_limit,
        ]);

        // The key's presence — not its emptiness — is the signal that the
        // module configuration was submitted. isset() returned false for an
        // empty selection, so "uncheck everything and save" silently changed
        // nothing. The request layer guarantees this key exists only when the
        // form actually carried the module section.
        if (array_key_exists('modules', $data) && is_array($data['modules'])) {
            $plan->modules()->sync($data['modules']);

            // Modules changed — every company on this plan must re-resolve.
            CompanySubscription::where('plan_id', $plan->id)
                ->pluck('company_id')
                ->each(fn ($cid) => CompanySubscription::forgetCache($cid));
        }

        return $plan;
    }

    private function createSubscription(Company $company, Plan $plan, array $data): CompanySubscription
    {
        return CompanySubscription::updateOrCreate(
            ['company_id' => $company->id],
            [
                'plan_id'    => $plan->id,
                'starts_at'  => $data['starts_at']  ?? now(),
                'expires_at' => $data['expires_at'] ?? null,
                'is_active'  => isset($data['sub_is_active']) ? (bool) $data['sub_is_active'] : true,
            ]
        );
    }
}