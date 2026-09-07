<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Binds the tenant Company for authenticated, stateless API requests.
 *
 * IdentifyTenant resolves the tenant from the host (custom domain or
 * subdomain) or from a {slug} route parameter. The Flutter app talks to the
 * central root domain and its routes carry no slug, so none of those three
 * strategies match and tenant() stays null for the whole request.
 *
 * That matters because a fair amount of shared code reads tenant() before
 * falling back to the authenticated user — CheckSubscription and
 * get_setting()'s Context A among them. Once Sanctum has authenticated the
 * request, the user's own company IS the tenant, and binding it here makes
 * every one of those paths behave exactly as it does on the web.
 *
 * Runs after auth:sanctum. Never overwrites a tenant that IdentifyTenant
 * already resolved — if the app happens to hit a tenant subdomain, that
 * resolution is the more specific one and wins.
 */
class BindTenantFromUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->company_id && ! tenant()) {
            $user->loadMissing('company');

            if ($user->company) {
                app()->instance('tenant', $user->company);
                app()->instance('tenant_mode', TenantResolver::MODE_SUBDOMAIN);

                // Backward-compat with controllers/middleware that read the
                // request attributes rather than tenant().
                $request->attributes->set('current_company_id', $user->company->id);
                $request->attributes->set('current_company', $user->company);
            }
        }

        return $next($request);
    }
}