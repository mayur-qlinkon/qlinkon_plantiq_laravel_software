<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user) {
            abort(403, 'Unauthorized access.');
        }

        if (! $user->isSuperAdmin()) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Only Super Administrators can access this area.');
        }

        return $next($request);
    }
}