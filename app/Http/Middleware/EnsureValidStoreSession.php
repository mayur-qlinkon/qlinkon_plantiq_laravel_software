<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidStoreSession
{
    /**
     * Validate the session store_id on every authenticated request.
     * Auto-heals stale sessions when a user's store assignment changes.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // active_store() resolves the correct store and heals the session
        active_store($user);

        return $next($request);
    }
}
