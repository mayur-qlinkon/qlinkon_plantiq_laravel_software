<?php

namespace App\Services\Auth;

use App\Models\User;

class SessionContextBuilder
{
    /**
     * Populates roles/stores/company_id into the session. Called both at
     * explicit login() and (via RebuildTenantSessionContext) after a
     * silent Remember-Me restore, which never runs through a controller.
     */
    public function build(User $user): void
    {
        session([
            'company_id' => $user->company_id,
            'roles'      => $user->roles->pluck('slug')->toArray(),
            'stores'     => $user->stores->pluck('id')->toArray(),
        ]);

        if (function_exists('active_store')) {
            active_store($user);
        }
    }

    public function isStale(User $user): bool
    {
        return session('company_id') !== $user->company_id
            || ! session()->has('roles');
    }
}