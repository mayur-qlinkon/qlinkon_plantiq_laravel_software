<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    /**
     * Variadic: `permission:a` checks one slug, `permission:a,b` passes when
     * the user holds ANY of them. has_permission() already implements the
     * any-of semantics for arrays — this is what the sidebar uses, so a route
     * can now be gated exactly the way its nav entry is.
     */
    public function handle(Request $request, Closure $next, ...$permissionSlugs)
    {
        $permissionSlug = count($permissionSlugs) === 1 ? $permissionSlugs[0] : $permissionSlugs;

        if (! has_permission($permissionSlug)) {
            // For AJAX/API requests, return JSON
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }

            // For standard web requests, show a 403 page
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
