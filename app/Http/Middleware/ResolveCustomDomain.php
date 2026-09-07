<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;

class ResolveCustomDomain
{
    public function handle(Request $request, Closure $next)
    {
        // IdentifyTenant (web group, runs before this) has already resolved
        // the company via custom_domain / subdomain / slug — in that priority.
        // We just verify a tenant was found for THIS host pattern.
        //
        // This middleware is attached to routes that ONLY make sense on a
        // tenant host (custom domain or subdomain) — not on the apex.
        $company = $request->attributes->get('current_company');
        $mode    = app()->bound('tenant_mode') ? app('tenant_mode') : null;

        if (! $company) {
            abort(404, 'This domain or subdomain is not configured in our system.');
        }

        // Block apex/slug access through this route group — these routes are
        // meant for tenant-hosts only. Slug-based access goes through storefront.php.
        if ($mode === \App\Services\Tenancy\TenantResolver::MODE_SLUG) {
            abort(404);
        }

        return $next($request);
    }
}