<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckStorefrontStatus
{
    public function handle(Request $request, Closure $next)
    {        
        $company = $request->attributes->get('current_company');

        // Slug mode: IdentifyTenant resolves company from the host (subdomain /
        // custom domain) BEFORE route params are bound. For slug-based access on
        // the apex or app-subdomain host, current_company is not yet set at
        // middleware time. Resolve it here from the {slug} route param instead.
        if (! $company && ($slug = $request->route('slug'))) {
            $company = Company::where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if ($company) {
                // Propagate so downstream middleware + controller can read it.
                $request->attributes->set('current_company',       $company);
                $request->attributes->set('current_company_id',    $company->id);
                $request->attributes->set('custom_domain_company', $company);
                // Also bind to service container so tenant() helper works.
                app()->instance('tenant',      $company);
                app()->instance('tenant_mode', 'slug');
            }
        }

        if (! $company) {
            abort(404);
        }

        // These routes carry no {store_slug}, so they represent the company's
        // primary store — the same one storefront orders are routed to. Falling
        // back to any active store keeps older tenants working while they have
        // not yet marked a primary.
        $store = Store::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();

        // A company with no store has nothing to show publicly.
        if (! $store) {
            abort(404);
        }

        // Publish downstream so controllers and views do not resolve it again.
        $request->attributes->set('current_store',    $store);
        $request->attributes->set('current_store_id', $store->id);

        // storefront_enabled is a real column on stores, not a key-value
        // setting, so it is read straight off the model.
        if (! $store->storefront_enabled) {
            return response()->view('storefront.maintenance', [
                'company' => $company,
                'store'   => $store,
            ], 503);
        }

        return $next($request);
    }
}