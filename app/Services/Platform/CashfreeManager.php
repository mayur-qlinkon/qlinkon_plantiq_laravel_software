<?php

namespace App\Services\Platform;

use App\Models\Company;
use Exception;

/**
 * CashfreeManager
 *
 * Resolves Cashfree credentials and returns a ready CashfreeService.
 * FULL parallel to RazorpayManager — supports Platform AND Tenant contexts.
 *
 * Credential sources:
 *   Platform → config('services.cashfree.*')            (platform .env keys)
 *   Tenant   → get_setting('cashfree_*', '', $company->id)  (per-company settings table)
 *
 * Usage — Platform (subscription renewals):
 *   CashfreeManager::forPlatform()->createOrder(...)
 *   CashfreeManager::forPlatform()->isOrderPaid($orderId)
 *   CashfreeManager::platformAppId()
 *   CashfreeManager::jsEnvironment()
 *
 * Usage — Specific tenant company:
 *   CashfreeManager::forCompany($company)->createOrder(...)
 *   CashfreeManager::forCompany($company)->verifyWebhookSignature(...)
 *   CashfreeManager::companyAppId($company)
 *
 * Usage — Auto-resolved current tenant (middleware sets app('tenant')):
 *   CashfreeManager::forCurrentTenant()->createOrder(...)
 *
 * Tenant setting keys (stored in company_settings table via set_setting()):
 *   cashfree_app_id         → Tenant's Cashfree App ID
 *   cashfree_secret_key     → Tenant's Cashfree Secret Key
 *   cashfree_webhook_secret → Tenant's webhook verification secret (same as secret_key in Cashfree)
 */
class CashfreeManager
{
    /** Cached instances per context (platform / company_{id}). Reset per request lifecycle. */
    protected static array $instances = [];

    // -------------------------------------------------------------------------
    // FACTORIES
    // -------------------------------------------------------------------------

    /**
     * Platform gateway — uses .env / config('services.cashfree.*').
     * Used for: subscription renewal payments to the platform.
     */
    public static function forPlatform(): CashfreeService
    {
        return static::$instances['platform'] ??= new CashfreeService(
            (string) config('services.cashfree.app_id'),
            (string) config('services.cashfree.secret_key')
        );
    }

    /**
     * A specific tenant company's gateway.
     * Uses keys stored per-company in the settings table via get_setting().
     *
     * Used for: tenant's own order payments (POS, invoices, storefront checkout).
     *
     * @throws Exception if tenant keys are not configured
     */
    public static function forCompany(Company $company): CashfreeService
    {
        $cacheKey = 'company_'.$company->id;

        return static::$instances[$cacheKey] ??= new CashfreeService(
            (string) get_setting('cashfree_app_id', '', $company->id),
            (string) get_setting('cashfree_secret_key', '', $company->id)
        );
    }

    /**
     * Current resolved tenant's gateway (tenant set by IdentifyTenant middleware).
     * Equivalent to RazorpayManager::forCurrentTenant().
     *
     * Used for: any request inside a tenant route group where app('tenant') is set.
     *
     * @throws Exception if no tenant is resolved
     */
    public static function forCurrentTenant(): CashfreeService
    {
        $company = tenant();

        if (! $company) {
            throw new Exception('No tenant resolved for Cashfree. Ensure IdentifyTenant middleware ran.');
        }

        return static::forCompany($company);
    }

    // -------------------------------------------------------------------------
    // CREDENTIAL HELPERS (for passing to frontend / views)
    // -------------------------------------------------------------------------

    /**
     * Platform App ID for the Cashfree JS SDK on the frontend.
     * Pass this to the blade view; never expose the secret key.
     */
    public static function platformAppId(): string
    {
        return (string) config('services.cashfree.app_id');
    }

    /**
     * Platform secret key — used for webhook verification only.
     * Never expose to frontend.
     */
    public static function platformSecretKey(): string
    {
        return (string) config('services.cashfree.secret_key');
    }

    /**
     * Tenant company's App ID.
     * Pass to frontend when processing tenant-level payments.
     */
    public static function companyAppId(Company $company): string
    {
        return (string) get_setting('cashfree_app_id', '', $company->id);
    }

    /**
     * Tenant company's webhook secret (same as their secret_key in Cashfree).
     * Used in tenant webhook controllers to verify signatures.
     */
    public static function companyWebhookSecret(Company $company): string
    {
        return (string) get_setting('cashfree_secret_key', '', $company->id);
    }

    /**
     * JS SDK environment string for the Cashfree Drop.js / Checkout SDK.
     * Returns "sandbox" or "production" based on CASHFREE_SANDBOX in .env.
     *
     * Usage in blade:
     *   const cashfree = Cashfree({ mode: @json($cashfreeEnv) });
     */
    public static function jsEnvironment(): string
    {
        return config('services.cashfree.sandbox', true) ? 'sandbox' : 'production';
    }

    // -------------------------------------------------------------------------
    // CACHE MANAGEMENT
    // -------------------------------------------------------------------------

    /**
     * Flush the cached instance for a specific company.
     * Call this after a tenant updates their Cashfree keys in settings.
     */
    public static function forgetCompany(Company $company): void
    {
        unset(static::$instances['company_'.$company->id]);
    }

    /**
     * Flush all cached instances (e.g. in tests or after config changes).
     */
    public static function flush(): void
    {
        static::$instances = [];
    }
}