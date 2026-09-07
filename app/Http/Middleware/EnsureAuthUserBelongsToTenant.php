<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Even with the tenant-scoped provider, this guarantees a logged-in user
 * can never keep browsing a DIFFERENT tenant's admin URL with a session
 * that was originally established for another company — e.g. an old
 * session/tab left open from Company A, later pointed at Company B's
 * slug or subdomain.
 */
class EnsureAuthUserBelongsToTenant
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $tenant = tenant();

        if (! $user || ! $tenant || $user->isSuperAdmin()) {
            return $next($request);
        }

        if ((int) $user->company_id !== (int) $tenant->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect(tenant_url('admin'))
                ->with('error', 'Please log in to this company account.');
        }

        return $next($request);
    }
}