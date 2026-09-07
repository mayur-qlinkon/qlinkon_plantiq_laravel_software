<?php

namespace App\Http\Middleware;

use App\Models\Hrm\Employee;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gate for employee self-service pages.
 *
 * These belong to the person, not to the HRM module — someone with an HR
 * profile must reach their own attendance, leave and salary slips without
 * consuming a paid HRM seat. The admin side of HRM stays behind module:hrm.
 */
class EnsureEmployeeProfile
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check() || ! Auth::user()->hasEmployeeProfile()) {
            abort(403, 'This page is only available to employees.');
        }

        // Employment status is re-checked on every request, not only at login.
        // TenantAdminAuthController applies the same rule when signing in, but
        // that check cannot reach a session opened before HR changed the
        // status — a terminated worker kept full write access for the rest of
        // the session lifetime.
        if (Auth::user()->employee->status !== Employee::STATUS_ACTIVE) {
            abort(403, 'Your employee account is no longer active. Contact your admin.');
        }

        return $next($request);
    }
}