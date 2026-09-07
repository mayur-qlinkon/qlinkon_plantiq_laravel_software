<?php

namespace App\Http\Middleware;

use App\Services\Hrm\AnnouncementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPendingAnnouncements
{
    public function __construct(
        protected AnnouncementService $announcementService
    ) {}

    /**
     * Block navigation when user has unacknowledged mandatory announcements.
     * AJAX requests get a 409 JSON so the frontend can show the popup.
     * Normal requests redirect to the user's dashboard (where the popup loads).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Allow announcement popup routes so users CAN acknowledge
        if ($request->is('admin/announcements-popup*')) {
            return $next($request);
        }

        // Allow logout
        if ($request->is('logout') || $request->is('admin/logout')) {
            return $next($request);
        }

        // Allow asset requests
        if ($request->is('storage/*') || $request->is('build/*')) {
            return $next($request);
        }

        // Use cached check — avoids DB query on every request
        if (! $this->announcementService->hasPendingMandatory($user)) {
            return $next($request);
        }

        // Allow both dashboards — popup will show there
        if ($request->routeIs('admin.dashboard') || $request->routeIs('admin.employee.dashboard')) {
            return $next($request);
        }

        // AJAX / SPA: return 409 so JS can trigger popup
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'announcement_pending' => true,
                'message' => 'You have mandatory announcements that require acknowledgement.',
            ], 409);
        }

        // Normal request: redirect to the user's appropriate dashboard
        $dashboardRoute = $user->employee ? 'admin.employee.dashboard' : 'admin.dashboard';

        return redirect()->route($dashboardRoute)
            ->with('warning', 'Please acknowledge all mandatory announcements before continuing.');
    }
}
