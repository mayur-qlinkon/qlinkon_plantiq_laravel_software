<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the "active store" for stateless API (Sanctum) requests.
 *
 * The existing admin panel resolves the current store via active_store(),
 * which reads/writes session('store_id'). That works fine for the web app
 * (session cookie persists across requests). A Flutter app using Sanctum
 * bearer tokens has no such persistent session, so instead:
 *
 *   1. The app sends the desired store on every request via the
 *      X-Store-Id header.
 *   2. This middleware validates that store belongs to the authenticated
 *      user (company-scoped, and store-assignment-scoped for non-admins),
 *      then seeds session('store_id') for the CURRENT request only.
 *   3. Every downstream call to active_store() / active_store_ids() /
 *      auth_stores() then resolves correctly, completely unchanged.
 *
 * The session started for this is ephemeral — no persistent cookie is
 * expected back from a native app, so it exists only for this request's
 * lifecycle. That's all active_store() needs.
 *
 * Company admins/owners may omit X-Store-Id only if they have exactly one
 * store — otherwise the app must ask the user to pick one via
 * GET /api/v1/me (which lists all assigned stores).
 */
class ResolveApiStoreContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        $requestedStoreId = $request->header('X-Store-Id') ?? $request->input('store_id');

        $store = null;

        if ($requestedStoreId) {
            $store = $this->resolveRequestedStore($user, (int) $requestedStoreId);

            if (! $store) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid or unassigned store_id for this user.',
                ], 422);
            }
        } else {
            $store = $this->resolveSingleAssignedStore($user);

            if (! $store) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Store selection required. Send X-Store-Id header — call GET /api/v1/me for the list of assigned stores.',
                ], 409);
            }
        }

        // Seed the per-request session so active_store() and friends
        // resolve exactly as they do in the web app — no changes needed
        // there at all.
        $request->session()->put('store_id', $store->id);

        return $next($request);
    }

    /**
     * Validate the requested store_id against what this user is allowed
     * to access — company admins may pick any company store, everyone
     * else only their assigned stores.
     */
    private function resolveRequestedStore($user, int $storeId): ?Store
    {
        if ($user->isCompanyAdmin()) {
            return Store::where('id', $storeId)
                ->where('company_id', $user->company_id)
                ->first();
        }

        return $user->stores()->where('stores.id', $storeId)->first();
    }

    /**
     * Auto-select when the user has exactly one store available, so the
     * app isn't forced to send a header for the common single-store case.
     */
    private function resolveSingleAssignedStore($user): ?Store
    {
        if ($user->isCompanyAdmin()) {
            $stores = Store::where('company_id', $user->company_id)->get();
        } else {
            $stores = $user->stores;
        }

        return $stores->count() === 1 ? $stores->first() : null;
    }
}