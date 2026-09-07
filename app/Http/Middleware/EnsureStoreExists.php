<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;

class EnsureStoreExists
{
    public function handle(Request $request, Closure $next)
    {
        // Positive session cache. Once a company has at least one store, that
        // fact effectively never reverts, so re-counting on every single admin
        // request is pure waste. The session is already loaded by StartSession,
        // so the fast path costs zero additional queries.
        //
        // Deliberately a POSITIVE cache only: a false/missing flag always falls
        // through to the real query, so a newly created store is picked up on
        // the very next request. The flag is cleared in Store::booted() when a
        // store is deleted.
        //
        // Trade-off, stated plainly: if another user deletes the last store,
        // this user's session flag stays stale until they log out or the flag
        // is cleared. The consequence is only that they are not force-redirected
        // to onboarding. This is a UX guard, not a security control, so the
        // stale window is acceptable.
        if ($request->session()->get('has_store') === true) {
            if ($request->routeIs('admin.onboarding.*')) {
                return redirect()->route('admin.dashboard');
            }

            return $next($request);
        }

        // Remember Phase 1? Because of the Tenantable trait, Store::count()
        // will ONLY count the stores belonging to the logged-in user's company!
        if (Store::count() === 0) {

            // If they are already on the onboarding page, let them through to prevent an infinite redirect loop
            if ($request->routeIs('admin.onboarding.*')) {
                return $next($request);
            }

            // Otherwise, redirect them to the onboarding setup
            return redirect()->route('admin.onboarding.index')
                ->with('warning', 'Welcome! Please set up your first store to get started.');
        }

        // At least one store exists — remember it for subsequent requests.
        $request->session()->put('has_store', true);

        // If they have a store but are trying to access onboarding, send them to the dashboard
        if ($request->routeIs('admin.onboarding.*')) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
