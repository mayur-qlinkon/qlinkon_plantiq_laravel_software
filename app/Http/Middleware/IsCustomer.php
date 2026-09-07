<?php

namespace App\Http\Middleware;

use Closure;
use App\Enums\Auth\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Ensures the authenticated user has a linked Client (customer) profile.
 * Replaces the old role:customer check — no Role model dependency.
 */
class IsCustomer
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect(tenant_url('login'));
        }

        // Ensure this is actually a customer: both user_type and client profile must exist
        // This prevents accidental portal access if data gets corrupted.
        if ($user->user_type !== UserType::CUSTOMER || ! $user->client) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'This portal is for customers only. Please log in with a customer account.');
        }

        return $next($request);
    }
}
