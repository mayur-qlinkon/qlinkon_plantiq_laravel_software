<?php

namespace App\Providers\Auth;

use Illuminate\Auth\EloquentUserProvider;
use App\Enums\Auth\UserType;

/**
 * Extends Laravel's default Eloquent provider with a tenant boundary on
 * top of the normal id/token lookups: "Remember Me must never authenticate
 * the user into another tenant" — a valid cookie/session for Company A's
 * user can never resolve while the current request is inside Company B's
 * tenant context.
 *
 * SCOPE OF THIS GUARANTEE: only applies when tenant() resolves — i.e.
 * storefront requests on a company subdomain/custom-domain/slug. On the
 * central admin domain (config('app.url') host, path-based /admin/*),
 * tenant() is always null, so this class does NOT scope id/token lookups
 * there. That's intentional and safe today: id/token lookups always
 * resolve to exactly one user row regardless of scope, and BlockClientAccess
 * middleware separately blocks any customer/client account that reaches
 * /admin/*. Do not treat this provider as tenant protection for the admin
 * domain — if that's ever needed, it has to be a separate, explicit check.
 *
 * Super admins aren't tied to a single tenant and
 * fall through unscoped, same as today.
 */
class TenantScopedUserProvider extends EloquentUserProvider
{
    /** Per-request memo — same $id doesn't need a second round-trip even if
     *  multiple guard resolutions happen within one request lifecycle. */
    protected static array $resolvedById = [];

    public function retrieveById($identifier)
    {
        if (array_key_exists($identifier, static::$resolvedById)) {
            return static::$resolvedById[$identifier];
        }

        $query = $this->newTenantScopedQuery();

        return static::$resolvedById[$identifier] = $query->find($identifier);
    }

    public function retrieveByToken($identifier, $token)
    {
        $query = $this->newTenantScopedQuery()->where(
            $this->createModel()->getAuthIdentifierName(),
            $identifier
        );

        $user = $query->first();

        if (! $user) {
            return null;
        }

        $rememberToken = $user->getRememberToken();

        return $rememberToken && hash_equals($rememberToken, $token) ? $user : null;
    }

    /**
     * Explicitly bypasses ANY global scopes on the User model (including
     * Tenantable's, which is keyed off Auth::user() and is unreliable to
     * depend on mid-authentication) and applies our own, deterministic
     * tenant filter instead.
     */
    protected function newTenantScopedQuery()
    {
        $model = $this->createModel();

        // Drop only Tenantable's scope — keep SoftDeletingScope, otherwise a
        // deleted user stays authenticated through session or remember token.
        $query = $model->newQuery()->withoutGlobalScope('tenant');

        $tenant = function_exists('tenant') ? tenant() : null;

        if ($tenant) {
            $query->where(function ($q) use ($tenant) {
                $q->where('company_id', $tenant->id)
                  ->orWhere('user_type', UserType::SUPER_ADMIN);
            });
        }

        return $query;
    }
}