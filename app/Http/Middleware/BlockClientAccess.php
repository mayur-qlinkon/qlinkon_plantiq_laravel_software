<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks customer/client users from every admin route.
 *
 * WHY THIS MIDDLEWARE EXISTS
 * ──────────────────────────
 * Customer accounts are created via the storefront registration flow. They use
 * the same default auth guard as admin/staff users, so Laravel's built-in
 * `auth` middleware alone cannot tell them apart — any logged-in customer can
 * satisfy `auth` and reach /admin/*.
 *
 * Detection: a customer user always has a linked `clients` row (set by
 * CustomerAuthController). Staff/owners never have one. This is the canonical,
 * role-free check used everywhere else in the codebase (RoleMiddleware,
 * LoginController, IsCustomer middleware).
 *
 * Action on detection:
 *   1. Fully log the user out and destroy their session so they cannot retry
 *      immediately with the same token.
 *   2. Return a 403 — never silently redirect to the storefront, which would
 *      leak whether the URL exists.
 *
 * PLACEMENT
 * ─────────
 * This middleware is intentionally placed in the OUTER admin route group
 * (after `auth`, before `subscription` / `announcements`) so it runs once
 * for every single /admin/* request — including the AI chatbot, dashboard,
 * and any future routes — with no per-route annotation required.
 */
class BlockClientAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Should not happen (auth middleware runs first), but be defensive.
        if (! $user) {
            return $next($request);
        }

        // `client` is a hasOne relation. Non-null means this is a customer account.
        if ($user->client !== null) {
            // Fully invalidate the session so the stolen-cookie scenario is also closed.
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Access denied. Please use your store login portal.');
        }

        return $next($request);
    }
}