<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\Request;

/**
 * Runs on every web request.
 * Resolves the current Company tenant and binds it to the service container.
 *
 * Does NOT abort — tenant may be null on apex/landing pages.
 * Use the 'tenant.required' alias on route groups that need a tenant.
 */
class IdentifyTenant
{
    public function __construct(protected TenantResolver $resolver) {}

    public function handle(Request $request, Closure $next)
    {
        $result = $this->resolver->resolve(
            $request,
            $request->route('slug'),   // null on non-slug routes
        );

        $company = $result['company'];

        // ── Bind globally ──────────────────────────────────────────────
        app()->instance('tenant',      $company);
        app()->instance('tenant_mode', $result['mode']);

        // ── Backward-compat: keep request attributes alive ─────────────
        // Existing controllers and middleware that read current_company_id
        // and custom_domain_company keep working without any changes.
        if ($company) {
            $request->attributes->set('current_company_id',     $company->id);
            $request->attributes->set('current_company',        $company);
            $request->attributes->set('custom_domain_company',  $company); // used by CheckStorefrontStatus
        }

        return $next($request);
    }
}