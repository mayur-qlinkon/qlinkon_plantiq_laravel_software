<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\CompanySubscription;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Prefer the already-resolved tenant (IdentifyTenant runs
        // globally, before this middleware, for slug, subdomain AND
        // custom-domain modes) — this is the URL the user is actually
        // ON, and is what must be checked, not necessarily the
        // logged-in user's own company.
        $company = tenant();

        // 2. Storefront fallback (rare — only if tenant() wasn't set)
        if (! $company) {
            $company = $request->attributes->get('current_company');
        }

        if (! $company) {
            // NOTE: segment(1) fallback jaan-bujh kar hataya hai — path-based
            // admin routes (/admin/*) ke liye segment(1) hamesha literal
            // string "admin" hoga, kabhi bhi real company slug nahi. Wo query
            // hamesha khali result deta tha, sirf wasted DB round-trip tha.
            $slug = $request->route('slug');
            if ($slug) {
                $company = Company::where('slug', $slug)->first();
            }
        }

        // 3. Last resort — logged-in user's own company (apex-domain admin login)
        if (! $company && Auth::check()) {
            $company = Auth::user()->company;
        }

        // Guest / no-tenant-context request (landing page, apex domain,
        // platform routes) — genuinely nothing to check, let it pass.
        if (! $company) {
            if (! Auth::check()) {
                return $next($request);
            }

            // Logged-in user but company failed to resolve — this is a
            // data/session integrity problem, NOT a "no tenant" case.
            // Never silently bypass the subscription gate here; block.
            Log::error('[CheckSubscription] Company could not be resolved for an authenticated user.', [
                'user_id' => Auth::id(),
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(500, 'Unable to resolve your company account. Please contact support.');
        }

        // 3. Company ka Subscription Status check karein
        //
        // Same resolver as has_module(), on purpose. This used to be a separate
        // Cache::remember() with its own key, its own TTL and no invalidation,
        // so an expiry set by a super admin stripped the tenant's modules while
        // this gate still reported active — an admin panel with nothing in it
        // and no redirect explaining why.
        $hasActiveSubscription = CompanySubscription::activeFor($company->id) !== null;

        // 4. Agar koi ACTIVE subscription nahi hai:
        if (! $hasActiveSubscription) {

            // Case A: Agar wahi Company Owner/Staff logged in hai, aur backend access karne ki koshish kar raha hai
            if (Auth::check() && (int) Auth::user()->company_id === (int) $company->id) {

                // Distinguish: "naya tenant jisne kabhi plan nahi liya" vs "purana
                // tenant jiska plan expire ho gaya". Dono ka destination alag hai.
                $hasEverSubscribed = CompanySubscription::where('company_id', $company->id)->exists();

                // ── New tenant — never had a subscription → pick a plan on landing ──
                if (! $hasEverSubscribed) {
                    // Already on landing? Let them through (no redirect loop).
                    if ($request->routeIs('landing') || $request->is('/')) {
                        return $next($request);
                    }

                    return redirect()->route('landing')
                        ->with('info', 'Welcome! Choose a plan to get started.');
                }

                // ── Existing tenant — subscription expired → renewal page ──
                // Infinite loop se bachne ke liye check karein ki wo already renewal page par toh nahi hai
                if ($request->routeIs('subscriptions.*')) {
                    return $next($request);
                }

                return redirect()->route('subscriptions.index')
                    ->with('error', 'Your subscription has expired. Please renew to continue.');
            }

            // Case B: Agar Guest User hai ya koi aur storefront visit kar raha hai
            // Toh site ko poori tarah band karke maintenance/unavailable page dikhao
            return response()->view('storefront.maintenance', [
                'company' => $company,
                // Aap chaho toh maintenance view me customized alert message bhi bhej sakte ho
                'message' => 'This store is temporarily unavailable due to billing adjustments.',
            ], 503);
        }

        return $next($request);
    }
}