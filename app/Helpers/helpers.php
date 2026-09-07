<?php


use App\Models\User;
use App\Models\Store;
use App\Models\Product;
use App\Models\Company;
use App\Models\Setting;
use App\Models\OcrScan;
use App\Models\Hrm\Employee;
use App\Models\UserModuleAccess;
use App\Models\CompanySubscription;
use App\Models\CompanyModuleLicense;
use App\Models\Platform\SystemSetting;

use Illuminate\Validation\Rules\Exists;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

if (! function_exists('get_setting')) {
    function get_setting($key = null, $default = null, ?int $forCompanyId = null)
    {
        static $settings = [];

        // 1. Identify the Company ID (The "Tenant")
        $companyId = null;

        // Context 0: Explicit company ID passed directly (e.g. from OrderService, jobs, commands)
        // This bypasses request/auth context entirely — useful for background processes
        if ($forCompanyId) {
            $companyId = $forCompanyId;
        }

        // Context A: Tenant host (subdomain / custom domain) — resolved by IdentifyTenant.
        // This handles: acme.smartbiz.in, clientshop.com, etc.
        // Checked BEFORE slug so subdomain context takes priority over any stale slug.
        elseif (app()->bound('tenant') && app('tenant')) {
            $companyId = app('tenant')->id;
        }

        // Context B: Public Storefront via slug (/{slug}/...)
        elseif (request()->route('slug')) {
            // First try the pre-set attribute (fastest — set by IdentifyTenant)
            $companyId = request()->attributes->get('current_company_id');

            // Fallback: resolve from slug directly if attribute not yet set
            // This handles view composers that fire before the controller sets it
            if (! $companyId) {
                $slug = request()->route('slug');
                $companyId = Cache::remember(
                    "company_slug_to_id_{$slug}",
                    3600,
                    fn () => Company::where('slug', $slug)->value('id')
                );

                if ($companyId) {
                    request()->attributes->set('current_company_id', $companyId);
                }
            }
        }

        // Context B: Admin Panel (Identify by Auth)
        if (! $companyId && Auth::check()) {
            $companyId = Auth::user()->company_id;
        }

        // If no company context is found (e.g., standard login page), return default
        if (! $companyId) {
            return is_null($key) ? (object) [] : $default;
        }

        // 2. Fetch and Cache (Memory + File Cache)
        //
        // The whole company is loaded in one query and kept as a nested map
        // [store_id => [key => value]]. Switching stores therefore costs no
        // extra query, and there is a single cache entry to invalidate.
        if (! isset($settings[$companyId])) {
            $settings[$companyId] = Cache::remember("company_settings_{$companyId}", 86400, function () use ($companyId) {
                return Setting::where('company_id', $companyId)
                    ->get(['store_id', 'key', 'value'])
                    ->groupBy('store_id')
                    ->map(fn ($rows) => $rows->pluck('value', 'key')->toArray())
                    ->toArray();
            });
        }

        $companyLevel = $settings[$companyId][Setting::COMPANY_LEVEL] ?? [];

        // 3. Return Logic
        if (is_null($key)) {
            return (object) $companyLevel;
        }

        return $companyLevel[$key] ?? $default;
    }
}

if (! function_exists('store_setting')) {
    /**
     * Read a setting that belongs to one store — billing prefixes, bank
     * details, invoice content, store SEO and contact info.
     *
     * There is deliberately no company-level fallback. A key lives either on
     * the company or on the store, never both, so a missing store value means
     * the store has genuinely not configured it.
     *
     * Usage:
     *   store_setting('invoice_prefix', 'INV-')
     *   store_setting('bank_name', null, $storeId)   // background jobs
     *   store_setting()                              // whole active-store map
     */
    function store_setting($key = null, $default = null, ?int $forStoreId = null)
    {
        $storeId = $forStoreId ?? active_store()?->id;

        // No store context at all — a company with no stores yet, or a public
        // request before the store is resolved.
        if (! $storeId) {
            return is_null($key) ? (object) [] : $default;
        }

        // Warm the shared map through get_setting(), then read this store's
        // bucket straight from the same cache entry.
        get_setting();

        $companyId = Auth::user()?->company_id
            ?? (app()->bound('tenant') && app('tenant') ? app('tenant')->id : null);

        $all = $companyId
            ? (array) Cache::get("company_settings_{$companyId}", [])
            : [];

        $storeLevel = $all[$storeId] ?? [];

        if (is_null($key)) {
            return (object) $storeLevel;
        }

        return $storeLevel[$key] ?? $default;
    }
}

if (! function_exists('forget_settings_cache')) {
    /**
     * Drop the cached settings map for a company. Call this after any write —
     * a stale entry would serve one store's billing prefix to another for up
     * to 24 hours.
     */
    function forget_settings_cache(?int $companyId = null): void
    {
        $companyId = $companyId ?? Auth::user()?->company_id;

        if ($companyId) {
            Cache::forget("company_settings_{$companyId}");
        }
    }
}

// ── Check if current user is super admin ──
// Single place — if you ever change detection logic, change here only
// Usage: is_super_admin() → true/false


if (! function_exists('is_super_admin')) {
    /**
     * Convenience wrapper around User::isSuperAdmin().
     * The model owns the logic; this only saves callers an Auth::user() call.
     */
    function is_super_admin(): bool
    {
        $user = Auth::user();

        return $user ? $user->isSuperAdmin() : false;
    }
}

if (! function_exists('is_company_admin')) {
    /**
     * Convenience wrapper around User::isCompanyAdmin().
     */
    function is_company_admin(): bool
    {
        $user = Auth::user();

        return $user ? $user->isCompanyAdmin() : false;
    }
}

if (! function_exists('has_employee_profile')) {
    /**
     * True when an HRM Employee record is attached to the current login.
     * Answers "is this person on the payroll?" — never "what can they do?".
     */
    function has_employee_profile(): bool
    {
        $user = Auth::user();

        return $user ? $user->hasEmployeeProfile() : false;
    }
}



if (! function_exists('active_store')) {
    /**
     * Resolve the active store for a user with automatic session healing.
     *
     * Priority:
     * 1. Session store_id if it exists in user's assigned stores (store_user pivot)
     * 2. First assigned store from store_user pivot
     * 3. Employee's store (employees.store_id) as fallback
     */
    function active_store(?User $user = null): ?Store
    {
        $user = $user ?? Auth::user();

        // Guard: guests and unauthenticated calls return null immediately.
        // This MUST come before any property/relation access on $user.
        if (! $user) {
            return null;
        }

        // Request-level memoization. active_store() is called by the admin
        // layout header, by active_store_ids(), and by many controllers within
        // a single request. Each uncached call for a company admin repeated the
        // same Store lookup, so a page could fire this query several times.
        static $memo = [];

        if (array_key_exists($user->id, $memo)) {
            return $memo[$user->id];
        }

        return $memo[$user->id] = active_store_resolve($user);
    }
}

if (! function_exists('active_store_resolve')) {
    /**
     * Uncached resolution logic behind active_store(). Do not call this
     * directly — always go through active_store() so the per-request memo
     * applies.
     */
    function active_store_resolve(User $user): ?Store
    {
        // Identity-based check — do not depend on the role row.
        if ($user->isCompanyAdmin()) {
            $sessionStoreId = session('store_id');
 
            if ($sessionStoreId) {
                $store = Store::where('id', $sessionStoreId)
                    ->where('company_id', $user->company_id)
                    ->first();
 
                if ($store) {
                    return $store; // ✅ Honour the switched store
                }
            }
 
            // No valid session store — honour the company's configured default
            // storefront store first (set in Settings → Storefront), then fall
            // back to the first store only if that default is missing/invalid.
            $defaultStoreId = get_setting('default_storefront_store_id', null, $user->company_id);

            if ($defaultStoreId) {
                $defaultStore = Store::where('id', $defaultStoreId)
                    ->where('company_id', $user->company_id)
                    ->first();

                if ($defaultStore) {
                    session(['store_id' => $defaultStore->id]);
                    return $defaultStore;
                }
            }

            // Default not set or invalid — fall back to first store.
            $store = Store::where('company_id', $user->company_id)->first();
            if ($store) {
                session(['store_id' => $store->id]);
                return $store;
            }
        }
 
        $stores = $user->stores;
        $sessionStoreId = session('store_id');
 
        // 1. Session store matches an assigned store — valid
        if ($sessionStoreId && $stores->isNotEmpty()) {
            $match = $stores->firstWhere('id', $sessionStoreId);
            if ($match) {
                return $match;
            }
        }
 
        // 2. Session is stale or missing — use first assigned store
        if ($stores->isNotEmpty()) {
            $fallback = $stores->first();
            session(['store_id' => $fallback->id]);
 
            return $fallback;
        }
 
        // 3. No pivot stores — fallback to employee's store
        $employee = $user->employee;
        if ($employee && $employee->store_id) {
            $employeeStore = $employee->store;
            if ($employeeStore) {
                session(['store_id' => $employeeStore->id]);
 
                return $employeeStore;
            }
        }
 
        return null;
    }
}

// ─────────────────────────────────────────────────────────────────────────
//  STORE SCOPE HELPERS  (added for multi-store access control)
// ─────────────────────────────────────────────────────────────────────────

if (! function_exists('auth_store_ids')) {
    /**
     * Returns the store IDs the current user may access.
     * null  → no restriction (owner / super admin sees all company stores)
     * array → only these store IDs (regular users see their assigned stores)
     *
     * Usage in controller index():
     *   $storeIds = auth_store_ids();
     *   $query->when($storeIds, fn($q) => $q->whereIn('store_id', $storeIds));
     */
    function auth_store_ids(): ?array
    {
        if (is_super_admin()) {
            return null;
        }

        $user = Auth::user();
        if (! $user) {
            return [];
        }

        // Owners see every store in their company (Tenantable already scopes company)
        if ($user->isCompanyAdmin()) {
            return null;
        }

        // Cache per-request to avoid repeated DB hits (helpers called many times per page)
        static $cache = [];
        $userId = $user->id;

        if (! array_key_exists($userId, $cache)) {
            $cache[$userId] = $user->stores()->pluck('stores.id')->toArray();
        }

        return $cache[$userId];
    }
}
if (! function_exists('active_store_ids')) {
    /**
     * Returns ONLY the currently switched/active store as an array.
     * Use this for filtering invoices, purchases, expenses — NOT for dropdowns.
     * Works identically for owners and regular users.
     */
    function active_store_ids(): array
    {
        if (is_super_admin()) {
            return [];
        }

        $store = active_store();
        return $store ? [$store->id] : [0];
    }
}
if (! function_exists('auth_stores')) {
    /**
     * Returns an Eloquent query builder for the stores the current user
     * is allowed to see — use this for ALL dropdown/select queries.
     *
     * Usage:
     *   $stores = auth_stores()->get();
     *   $stores = auth_stores()->orderBy('name')->get();
     */
    function auth_stores(): \Illuminate\Database\Eloquent\Builder
    {
        $storeIds = auth_store_ids();

        $query = Store::where('is_active', true);

        if ($storeIds !== null) {
            // Non-owner: restrict to assigned stores only.
            // [0] guard prevents an accidental "no stores = see everything" leak.
            $query->whereIn('id', empty($storeIds) ? [0] : $storeIds);
        }

        return $query;
    }
    
}

if (! function_exists('tenant_subscription')) {
    /**
     * Get the current active subscription for the logged-in user's company.
     *
     * Two-layer cache:
     *   1. Static PHP array — zero overhead after first call within a request (eliminates
     *      the cache driver round-trip on the 20+ has_module() calls per page).
     *   2. Laravel cache driver — persists across requests so the DB is rarely hit.
     */
    function tenant_subscription()
    {
        if (! Auth::check() || ! Auth::user()->company_id) {
            return null;
        }

        return CompanySubscription::activeFor(Auth::user()->company_id);
    }
}

if (! function_exists('max_upload_bytes')) {
    /**
     * The real ceiling for a single uploaded file on this server.
     *
     * Read from PHP rather than hard-coded, so the number shown in the UI is
     * always the number actually enforced — on shared hosting these values
     * differ per plan and can change without notice.
     *
     * post_max_size matters as much as upload_max_filesize: exceed it and PHP
     * discards the entire request body, so the CSRF token vanishes too and the
     * user sees "419 Page Expired" instead of a size error. A small reserve is
     * subtracted for the other form fields riding along in the same POST.
     */
    function max_upload_bytes(): int
    {
        $toBytes = function (string $value): int {
            $value = trim($value);

            if ($value === '') {
                return 0;
            }

            $number = (int) $value;

            return match (strtolower(substr($value, -1))) {
                'g' => $number * 1024 ** 3,
                'm' => $number * 1024 ** 2,
                'k' => $number * 1024,
                default => $number,
            };
        };

        // A value of 0 means unlimited, so it is dropped rather than winning min().
        $limits = array_filter([
            $toBytes((string) ini_get('upload_max_filesize')),
            $toBytes((string) ini_get('post_max_size')),
        ]);

        if ($limits === []) {
            return 20 * 1024 * 1024;
        }

        return max(0, min($limits) - (512 * 1024));
    }
}

if (! function_exists('has_module')) {
    /**
     * Check if the current user can access a specific module.
     *
     * Two layers, in order:
     *   1. Company-level: does the company's plan include this module? (unchanged check)
     *   2. User-level: per-user seat licensing (company_module_licenses + user_module_access)
     *
      * Missing CompanyModuleLicense row for a plan-included module is treated
     * as "user not assigned," not "everyone allowed." Module assignment must
     * never be bypassed just because a license row hasn't been seeded.
     */
    function has_module($moduleSlug)
    {
        if (is_super_admin()) {
            return true;
        }

        $subscription = tenant_subscription();

        if (! $subscription || ! $subscription->plan) {
            return false;
        }

        $user = Auth::user();

        static $memo = [];

        $cacheKey = $subscription->id.'|'.$user->id.'|'.(
            is_array($moduleSlug)
                ? implode(',', $moduleSlug)
                : $moduleSlug
        );

        if (array_key_exists($cacheKey, $memo)) {
            return $memo[$cacheKey];
        }

        $modules = $subscription->plan->modules;
        $slugs = is_array($moduleSlug) ? $moduleSlug : [$moduleSlug];

        foreach ($slugs as $slug) {
            // Layer 1: company plan must include the module
            if (! $modules->contains('slug', $slug)) {
                continue;
            }

            // Layer 2: per-user seat licensing
            if (user_can_access_module($user, $slug)) {
                return $memo[$cacheKey] = true;
            }
        }

        return $memo[$cacheKey] = false;
    }

    // usage
    /*
            @if(has_module('invoice'))
                <a href="{{ route('invoices.index') }}" class="nav-item {{ $navCls(['invoices.*']) }}">
                    <span class="flex items-center gap-3">
                        <i data-lucide="file-text" class="nav-icon w-[18px] h-[18px]"></i> Invoices
                    </span>
                </a>
            @endif
    */
}

if (! function_exists('user_can_access_module')) {
    /**
     * Per-user seat-licensing check for ONE module slug, assuming the company's
     * plan already includes it (caller's responsibility — has_module() does this).
     *
     * Owner always passes. Module assignment is a hard gate: a missing
     * CompanyModuleLicense row for a module the company's plan includes is
     * treated as "not assigned," never as "everyone gets access." (Previous
     * backward-compat fallback removed — it was letting unassigned users
     * reach modules whose license row hadn't been seeded yet.)
     */
    function user_can_access_module(User $user, string $moduleSlug): bool
    {
        static $memo = [];

        $cacheKey = $user->id.'|'.$moduleSlug;

        if (array_key_exists($cacheKey, $memo)) {
            return $memo[$cacheKey];
        }

        // No identity-based bypass. The owner pays for a seat like everyone
        // else, otherwise one CRM licence silently serves two people.
        //
        // An HR profile no longer grants the HRM module either. Self-service
        // pages (My Attendance, My Leaves, My Salary Slips) are gated by
        // has_employee_profile() instead, so an employee reaches their own
        // records without being handed the admin side of HRM.

        $license = CompanyModuleLicense::where('company_id', $user->company_id)
            ->whereHas('module', fn ($q) => $q->where('slug', $moduleSlug))
            ->first();

       // No license row = module not explicitly assigned to any user yet.
        // Deny, never silently allow — module assignment is the first
        // security layer and must never be bypassed by a missing row.
        if (! $license) {
            return $memo[$cacheKey] = false;
        }

        if (! $license->isCurrentlyValid()) {
            return $memo[$cacheKey] = false;
        }

        return $memo[$cacheKey] = UserModuleAccess::where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->where('module_id', $license->module_id)
            ->exists();
    }
}

if (! function_exists('check_plan_limit')) {
    /**
     * Check if the tenant has hit their resource limit ('users', 'stores', 'products', or 'employees').
     */
    function check_plan_limit($resourceType)
    {
        $subscription = tenant_subscription();
        if (! $subscription || ! $subscription->plan) {
            return false;
        }

        if ($resourceType === 'users') {
            // internal() excludes customer-role and client-linked users — staff only.
            // A seat is consumed only when a user actually holds a module
            // licence. Employees with no module access are billed through
            // employee_limit instead, never against user_limit.
            return User::internal()->whereHas('modules')->count() < $subscription->plan->user_limit;
        }

        if ($resourceType === 'stores') {
            return Store::count() < $subscription->plan->store_limit;
        }

        if ($resourceType === 'products') {
            return Product::count() < $subscription->plan->product_limit;
        }

        if ($resourceType === 'employees') {
            return Employee::count() < $subscription->plan->employee_limit;
        }

        // ── AI Chat Daily Limit Check ──────────────────────────────────────
        if ($resourceType === 'ai_chats') {
            $limit = $subscription->plan->ai_chat_daily_limit ?? 0;

            // 0 = no access (shouldn't reach here if module gate works, but safety net)
            if ($limit === 0) {
                return false;
            }

            // -1 = unlimited (premium plans)
            if ($limit === -1) {
                return true;
            }

            // Atomic cache counter — one key per company per day.
            // TTL 25h ensures it survives midnight and expires cleanly.
            $companyId = Auth::user()->company_id;
            $todayKey  = "ai_chat_usage_{$companyId}_" . now()->format('Y-m-d');
            $used      = (int) Cache::get($todayKey, 0);

            return $used < $limit;
        }

        // ── AI Token Daily Limit Check (primary cost guard) ────────────────
        // Enforces the per-tenant, per-day TOKEN budget. This is the strict cap
        // that actually controls spend, since cost scales with tokens not msgs.
        if ($resourceType === 'ai_tokens') {
            $limit = (int) ($subscription->plan->ai_token_daily_limit ?? 0);

            // 0 = no access. -1 = unlimited.
            if ($limit === 0)  return false;
            if ($limit === -1) return true;

            // Pre-check: block once today's budget is already spent. The final
            // allowed request may overshoot by at most one response (output is
            // capped at maxOutputTokens), which is the standard soft ceiling.
            $used = (int) Cache::get(ai_token_usage_key(), 0);

            return $used < $limit;
        }

        // ── OCR Scan Daily Limit Check ──
        if ($resourceType === 'ocr_scans') {
            $limit = (int) ($subscription->plan->ocr_scan_limit ?? 0);

            // 0 = no access. -1 = unlimited. The old `<= 0` test swallowed -1
            // and locked unlimited plans out of the module entirely.
            if ($limit === 0)  return false;
            if ($limit === -1) return true;

            return ocr_scans_used_today() < $limit;
        }

        return false;
    }
}
if (! function_exists('ocr_scans_used_today')) {
    /**
     * OCR scans this tenant has consumed today.
     *
     * Every row created today counts, including failed ones — a failed scan
     * still costs a billable Gemini Vision call, so it must spend quota.
     *
     * company_id is stated explicitly and the global scope is dropped, so the
     * count stays correct even on a request where the Tenantable scope never
     * registered. A BETWEEN range replaces whereDate() so the existing
     * (company_id, created_at) index is actually used.
     */
    function ocr_scans_used_today(): int
    {
        $companyId = Auth::user()?->company_id;

        // No tenant context = fail closed, never fail open.
        if (! $companyId) {
            return PHP_INT_MAX;
        }

        return OcrScan::withoutGlobalScope('tenant')
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->count();
    }
}

if (! function_exists('ai_token_usage_key')) {
    /**
     * Cache key for today's cumulative AI token usage for the current tenant.
     */
    function ai_token_usage_key(): string
    {
        $companyId = Auth::user()?->company_id ?? 0;
        return "ai_token_usage_{$companyId}_" . now()->format('Y-m-d');
    }
}

if (! function_exists('ai_chat_usage_key')) {
    /**
     * Cache key for today's AI message count for the current tenant.
     */
    function ai_chat_usage_key(): string
    {
        $companyId = Auth::user()?->company_id ?? 0;
        return "ai_chat_usage_{$companyId}_" . now()->format('Y-m-d');
    }
}

if (! function_exists('record_ai_usage')) {
    /**
     * Record one AI chat turn: bump the daily message counter and add the
     * turn's token cost to the daily token counter.
     *
     * IMPORTANT (Laravel 12 + database cache store):
     *   Cache::increment() returns false and does NOT create the row when the
     *   key is missing. That is exactly why the old daily limit never fired —
     *   the very first call of the day silently no-op'd. We seed the key with
     *   Cache::add(0, ttl) first, which creates it (and sets the 25h TTL), then
     *   increment(). add() is a no-op if the key already exists, so the TTL is
     *   set exactly once per day and increments never reset it.
     *
     * @param int $tokens Gemini tokens this turn consumed (0 for PHP/small-talk).
     */
    function record_ai_usage(int $tokens = 0): void
    {
        $ttl = now()->addHours(25);

        // Daily message count (coarse cap + usage widget).
        $msgKey = ai_chat_usage_key();
        Cache::add($msgKey, 0, $ttl);
        Cache::increment($msgKey);

        // Daily token count (strict cost cap). Only when real tokens were spent.
        if ($tokens > 0) {
            $tokKey = ai_token_usage_key();
            Cache::add($tokKey, 0, $ttl);
            Cache::increment($tokKey, $tokens);
        }
    }
}

if (! function_exists('has_permission')) {
    /**
     * Check if the authenticated user has a specific permission slug.
     * Automatically grants true if the user is the 'owner'.
     *
     * Static cache eliminates redundant in-memory lookups across the 54+ sidebar calls
     * on every page load. The cache key combines user ID + permission(s) so impersonation
     * or multi-user CLI contexts remain correct.
     */
    function has_permission($permissionSlug)
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        static $memo = [];

        $cacheKey = $user->id.'|'.(is_array($permissionSlug) ? implode(',', $permissionSlug) : $permissionSlug);

        if (array_key_exists($cacheKey, $memo)) {
            return $memo[$cacheKey];
        }

        if (is_super_admin()) {
            return $memo[$cacheKey] = true;
        }

        // 1. Check if they are the owner (owners can do everything)
        if ($user->isCompanyAdmin()) {
            return $memo[$cacheKey] = true;
        }

        // If array passed, loop through
        if (is_array($permissionSlug)) {
            foreach ($permissionSlug as $slug) {
                foreach ($user->roles as $role) {
                    if ($role->permissions->contains('slug', $slug)) {
                        return $memo[$cacheKey] = true;
                    }
                }
            }

            return $memo[$cacheKey] = false;
        }

        // 2. Check if their assigned role has the specific permission
        foreach ($user->roles as $role) {
            if ($role->permissions->contains('slug', $permissionSlug)) {
                return $memo[$cacheKey] = true;
            }
        }

        return $memo[$cacheKey] = false;
    }
}

// ── Check if a platform feature is enabled ──
// Reads from system_settings table — super admin controls this
// NOT the same as plan modules (CheckModuleAccess handles that)
// Usage: feature_enabled('crm')   → checks system_settings key 'feature_crm'
//        feature_enabled('pos')   → checks system_settings key 'feature_pos'
//
// In blade:   @if(feature_enabled('crm'))
// In routes:  ->middleware('feature:crm')


if (! function_exists('feature_enabled')) {
    function feature_enabled(string $feature): bool
    {
        // Default to true (enabled) if key not set — safe fallback
        // Prevents features from being accidentally blocked if key missing
        return SystemSetting::isEnabled("feature_{$feature}", true);
    }
}
if (! function_exists('batch_enabled')) {
    function batch_enabled(): bool {
        return (bool) get_setting('enable_batch_tracking', 0);
    }
}
// ── Check if platform is in maintenance mode ──
// Usage: is_maintenance_mode() → true/false
// Used by: MaintenanceMiddleware (Phase 2)


if (! function_exists('is_maintenance_mode')) {
    function is_maintenance_mode(): bool
    {
        return SystemSetting::isEnabled('maintenance_mode');
    }
}

// ── Get active platform announcement (if any) ──
// Returns null if no announcement or announcement is disabled
// Usage: platform_announcement() → string or null


if (! function_exists('platform_announcement')) {
    function platform_announcement(): ?string
    {
        $isActive = SystemSetting::isEnabled('platform_announcement_active');
        if (! $isActive) {
            return null;
        }

        $text = SystemSetting::getSetting('platform_announcement_text');

        return $text ?: null;
    }
}

// ── Get a system-level setting (platform / super admin) ──
// Wrapper around SystemSetting::getSetting() for convenience
// Usage: get_system_setting('maintenance_mode')
//        get_system_setting('maintenance_message', 'We are back soon!')


if (! function_exists('get_system_setting')) {
    function get_system_setting(string $key, mixed $default = null): mixed
    {
        return SystemSetting::getSetting($key, $default);
    }
}


// ─────────────────────────────────────────────────────────────────────────
// PUBLIC STOREFRONT: Resolve store from route {store_slug}
// Used by the new store-level public controller and middleware
// ─────────────────────────────────────────────────────────────────────────

if (! function_exists('resolve_public_store')) {
    function resolve_public_store(string $companySlug, string $storeSlug): ? Store
    {
        $cacheKey = "public_store_{$companySlug}_{$storeSlug}";

        return Cache::remember($cacheKey, 600, function () use ($companySlug, $storeSlug) {
            return Store::whereHas('company', fn ($q) => $q->where('slug', $companySlug))
                ->where('slug', $storeSlug)
                ->where('is_active', true)
                ->first();
        });
    }
}


if (! function_exists('homeUrl')) {

    /**
     * Return the correct "home" URL for any user type, or the landing page
     * for guests. Safe to call from error pages and layouts where no route
     * context may be available.
     *
     * Resolution order:
     *   guest            → landing page (/)
     *   super_admin      → platform dashboard
     *   client/customer  → storefront portal dashboard (needs company slug)
     *   employee type    → HRM employee dashboard
     *   company admin    → admin dashboard
     *   full staff       → admin dashboard
     *   unknown          → landing page (safe fallback, never a broken URL)
     */
    function homeUrl(): string
    {
        $user = Auth::user();

        // ── Guest: send to the landing page, never to a slug-dependent URL ──
        if (! $user) {
            return route('welcome');
        }

        // ── Super Admin (platform level) ──
        if ($user->isSuperAdmin()) {
            return route('platform.dashboard');
        }

        // ── Client / Customer — MUST use ->client property, not ->client() method ──
        // ->client() returns the Eloquent relation builder (always truthy).
        // ->client is the loaded model or null (correct null-check).
        if ($user->client !== null) {
            $slug = $user->company?->slug
                ?? request()->route('slug')
                ?? null;

            if ($slug) {
                return route('storefront.portal.dashboard', ['slug' => $slug]);
            }

            // Slug not resolvable (edge case: company deleted). Fall through.
        }

        // ── Every internal user lands on the admin panel. WHICH screen they
        //    land on is decided by DashboardResolver — the single source of
        //    truth, shared with both login controllers and the PWA start_url
        //    redirect (see redirectUsersTo() in bootstrap/app.php). ──
        if ($user->company_id) {
            $routeName = app(\App\Services\Platform\DashboardResolver::class)
                ->landingRouteName($user);

            // On a dedicated tenant host, route() rebuilds the URL from
            // APP_URL and would bounce the user to the wrong host. Slug mode
            // is deliberately untouched: admin routes carry no slug prefix,
            // so route() is already correct there.
            if (in_array(tenant_mode(), ['subdomain', 'custom_domain'], true)) {
                return tenant_url(route($routeName, [], false));
            }

            return route($routeName);
        }

        // ── Ultimate safe fallback — never a route that needs a slug ──
        return route('welcome');
    }

}

// ═══════════════════════════════════════════════════════════════════════
//  TENANT CONTEXT HELPERS
//  Read the Company resolved by IdentifyTenant middleware.
//  These are the single source of truth for all public-facing context.
// ═══════════════════════════════════════════════════════════════════════

if (! function_exists('tenant')) {
    /**
     * The current resolved Company tenant.
     * Returns null on apex / landing pages where no tenant is needed.
     */
    function tenant(): ?Company
    {
        return app()->bound('tenant') ? app('tenant') : null;
    }
}

if (! function_exists('tenant_mode')) {
    /**
     * How this tenant was resolved: 'slug' | 'subdomain' | 'custom_domain'
     * Useful for generating correct URLs and debugging.
     */
    function tenant_mode(): ?string
    {
        return app()->bound('tenant_mode') ? app('tenant_mode') : null;
    }
}
if (! function_exists('tenant_store')) {  
    function tenant_store() { return app()->bound('tenant_store') ? app('tenant_store') : null; }

}
if (! function_exists('tenant_url')) {
    /**
     * Generate a URL that stays in the current tenant's access mode.
     *
     * subdomain:     https://acme.smartbiz.in/{path}
     * custom_domain: https://clientshop.com/{path}
     * slug:          https://smartbiz.in/acme/{path}
     */
    function tenant_url(string $path = ''): string
    {
        $company = tenant();
        $mode    = tenant_mode();
        $path    = ltrim($path, '/');

        if ($company && in_array($mode, ['custom_domain', 'subdomain'], true)) {
            // Preserve the actual tenant host — DO NOT use url() which uses APP_URL.
            $scheme = request()->getScheme();
            $host   = request()->getHost();
            return $scheme . '://' . $host . ($path ? '/' . $path : '');
        }

        if ($company) {
            // Slug mode: prefix with company slug
            return url(trim($company->slug . '/' . $path, '/'));
        }

        return url($path);
    }
    
}


if (! function_exists('company_exists')) {
    /**
     * An `exists` rule scoped to the current company.
     *
     * Laravel's `exists:` runs a raw query builder lookup — Eloquent global
     * scopes never apply to it. So `'exists:stores,id'` on a Tenantable table
     * means "any company's store", and the accepted id is then written to a
     * column or pivot as if it were the tenant's own.
     *
     * Written as a helper because the safe form was three lines and the unsafe
     * form was one; this makes the safe form the shorter one.
     *
     *     'store_id' => ['required', tenant_exists('stores')],
     *
     * Fails closed when there is no company context: a null company_id matches
     * no row, so a request without a tenant validates nothing through.
     *
     * @param  string  $table   Table to look in.
     * @param  string  $column  Column to match the value against.
     */
    function company_exists(string $table, string $column = 'id'): Exists
    {
        return \Illuminate\Validation\Rule::exists($table, $column)
            ->where('company_id', Auth::user()?->company_id);
    }
}

if (! function_exists('company_or_host_exists')) {
    /**
     * Like tenant_exists(), but resolves the company from the host when there
     * is no authenticated user.
     *
     * Needed by requests shared between the admin panel and the public
     * storefront — StoreOrderRequest is used by both. Auth alone would fail
     * closed on every guest checkout; tenant() alone would be null on the
     * central admin domain. Trying Auth first keeps the admin path unchanged.
     */
    function company_or_host_exists(string $table, string $column = 'id'): Exists
    {
        $companyId = Auth::user()?->company_id ?? tenant()?->id;

        return \Illuminate\Validation\Rule::exists($table, $column)
            ->where('company_id', $companyId);
    }
}
if (! function_exists('storefront_store')) {
    /**
     * The Store whose details the public storefront represents.
     *
     * active_store() cannot be used here: it starts from Auth::user() and
     * returns null the moment there is no session, which is every storefront
     * request. This resolver works purely from the request's tenant context.
     *
     * Resolution order mirrors Store::booted(), which already guarantees at
     * most one primary store per company:
     *   1. the company's primary store
     *   2. any active store, oldest first
     *
     * Memoized per request — the storefront layout reads a dozen settings
     * from it in a single render.
     */
    function storefront_store(?int $forCompanyId = null): ?Store
    {
        static $memo = [];

        $companyId = $forCompanyId
            ?? (app()->bound('tenant') && app('tenant') ? app('tenant')->id : null);

        // Slug route (/{slug}/...) — same lookup get_setting() already caches.
        if (! $companyId && request()->route('slug')) {
            $companyId = request()->attributes->get('current_company_id');

            if (! $companyId) {
                $slug = request()->route('slug');
                $companyId = Cache::remember(
                    "company_slug_to_id_{$slug}",
                    3600,
                    fn () => Company::where('slug', $slug)->value('id')
                );
            }
        }

        if (! $companyId) {
            return null;
        }

        if (array_key_exists($companyId, $memo)) {
            return $memo[$companyId];
        }

        return $memo[$companyId] = Store::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();
    }
}

if (! function_exists('storefront_setting')) {
    /**
     * Read a storefront-facing value from the primary store.
     *
     * Store columns are the single source of truth for anything the public
     * sees — SEO, tagline, contact, social links, business hours, logo. There
     * is deliberately no company-level fallback: get_setting() reads the
     * settings table, which is a separate store of the same keys, and silently
     * falling back to it would let a stale company row mask an empty store
     * field instead of surfacing it.
     *
     * Blank strings are treated as unset, so an emptied admin field falls
     * through to $default rather than rendering an empty tag.
     */
    function storefront_setting(string $key, $default = null)
    {
        $store = storefront_store();

        if (! $store) {
            return $default;
        }

        $value = $store->{$key} ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }
}



if (! function_exists('asset_v')) {
    /**
     * asset() with a cache-busting fingerprint from the file's mtime.
     *
     * Without this the browser holds a locally-edited asset indefinitely:
     * editing helpers.js and reloading serves the stale copy, so newly added
     * functions read as undefined at call time. On shared hosting this also
     * means users keep running the previous deploy's JavaScript.
     *
     * Falls back to a plain asset() URL when the file is missing, so a bad
     * path fails loudly as a 404 rather than silently here.
     */
    function asset_v(string $path): string
    {
        $full = public_path($path);

        return file_exists($full)
            ? asset($path) . '?v=' . filemtime($full)
            : asset($path);
    }
}