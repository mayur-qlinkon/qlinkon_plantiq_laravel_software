<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResolveStorePublic
{
    public function handle(Request $request, Closure $next)
    {
        $companySlug = $request->route('slug');
        $storeSlug   = $request->route('store_slug');

        // 1. Resolve company
        $company = Company::where('slug', $companySlug)
            ->where('is_active', true)
            ->first();

        if (! $company) {
            abort(404, 'Shop not found.');
        }

        // 2. Resolve store
        $store = Store::where('company_id', $company->id)
            ->where('slug', $storeSlug)
            ->where('is_active', true)
            ->first();

        if (! $store) {
            abort(404, 'Branch not found.');
        }

        // 3. Publish to request attributes (available in controllers + Blade via $request)
        $request->attributes->set('current_company_id', $company->id);
        $request->attributes->set('current_company',    $company);
        $request->attributes->set('current_store_id',   $store->id);
        $request->attributes->set('current_store',      $store);

        // 4. Maintenance check (company staff bypass)
        $isStaff = Auth::check() && Auth::user()->company_id === $company->id;

        if (! $store->storefront_enabled && ! $isStaff) {
            return response()->view('storefront.maintenance', [
                'company' => $company,
                'store'   => $store,
            ], 503);
        }

        return $next($request);
    }
}