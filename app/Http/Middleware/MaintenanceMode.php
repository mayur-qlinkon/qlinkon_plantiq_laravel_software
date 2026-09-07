<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks the public landing page when maintenance mode is enabled in system_settings.
 *
 * Admin (/admin/*) and platform (/platform/*) routes are never blocked — super admins
 * must still be able to access the dashboard to toggle the setting off.
 */
class MaintenanceMode
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (is_maintenance_mode()) {
            return response()->view('maintenance', [], 503);
        }

        return $next($request);
    }
}
