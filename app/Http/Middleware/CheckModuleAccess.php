<?php

namespace App\Http\Middleware;

use App\Models\CompanySubscription;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckModuleAccess
{
    public function handle(Request $request, Closure $next, $moduleSlug)
    {
        $user = Auth::user();

        // Let super admins pass through
        if (! $user || ! $user->company_id) {
            return $next($request);
        }

        if (! tenant_subscription()?->plan) {
            abort(403, 'No active subscription found.');
        }

        // Delegate to has_module() so the URL and the sidebar answer the same
        // question. Checking only the plan here let any user reach a module
        // their company owns but they were never assigned a seat for.
        if (! has_module($moduleSlug)) {
            abort(403, 'You do not have access to this module. Please contact your administrator.');
        }

        return $next($request);
    }
}
