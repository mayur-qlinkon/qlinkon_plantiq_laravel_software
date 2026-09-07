<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\User;
use App\Services\Auth\SessionContextBuilder;
use App\Services\Platform\DashboardResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TenantAdminAuthController extends Controller
{
    // ════════════════════════════════════════════════════
    //  SHOW LOGIN FORM
    // ════════════════════════════════════════════════════
    public function showLoginForm()
    {
        $company = tenant() ?? abort(404, 'Store not found.');

        if (Auth::check() && Auth::user()->company_id === $company->id) {
            return redirect(homeUrl());
        }

        return view('auth.login', compact('company'));
    }

    // ════════════════════════════════════════════════════
    //  LOGIN LOGIC
    // ════════════════════════════════════════════════════
    public function login(Request $request)
    {
        $company = tenant() ?? abort(404);

        // 'email' input is now acting as a generic 'login_id' (Email OR Employee Code)
        $request->validate([
            'email'    => ['required', 'string'],
            'password' => ['required'],
        ]);

        $loginId = $request->email;

        // 🌟 THE ISOLATION LOCK & SMART LOOKUP 🌟
        // Find user by Email OR Employee Code strictly within THIS tenant
        $user = User::where('company_id', $company->id)
            ->where(function ($query) use ($loginId) {
                $query->where('email', $loginId)
                      ->orWhereHas('employee', function ($q) use ($loginId) {
                          $q->where('employee_code', $loginId);
                      });
            })->first();

        // Verify user exists and password is correct
        if (! $user || ! Hash::check($request->password, $user->password)) {
            Log::warning('[Tenant Admin] Failed Login Attempt', [
                'login_id' => $loginId,
                'company'  => $company->slug,
            ]);

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match this company or you do not have admin access.',
            ]);
        }

        // 🛑 SECURITY CHECK 1: Explicitly block Super Admins
        if ($user->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'email' => 'Super Admins must use the global /admin portal.',
            ]);
        }

        // 🛑 SECURITY CHECK 2: Block Customers & Inactive users
        if ($user->isCustomer() || $user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => 'Your account is inactive or not authorized for admin access.',
            ]);
        }

        // 🛑 SECURITY CHECK 3: Block terminated / absconding / on-notice /
        // inactive employees. HR can change an employee's status on the
        // profile screen without touching the linked login (User.status
        // stays 'active'), so this must be checked independently here —
        // relying on Check 2 alone lets a terminated employee keep logging
        // in until someone remembers to deactivate the account by hand.
        if ($user->employee && $user->employee->status !== Employee::STATUS_ACTIVE) {
            Log::warning('[Tenant Admin] Blocked login — employee not active', [
                'user_id' => $user->id,
                'employee_status' => $user->employee->status,
                'company' => $company->slug,
            ]);

            throw ValidationException::withMessages([
                'email' => 'Your employee account is not active. Contact your admin.',
            ]);
        }

        // If all checks pass, log the user in.
        // Auth::login() already generates + persists the remember token
        // via the configured user provider when $remember is true — do
        // NOT regenerate it afterwards, or the cookie's token and the
        // DB's token end up mismatched and Remember Me silently breaks
        // the moment the session expires.
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Store Session Context (Roles, Company ID, Stores)
        app(SessionContextBuilder::class)->build($user);

        Log::info('[Tenant Admin] User Logged In', [
            'user_id'   => $user->id,
            'user_type' => $user->user_type->value,
            'company'   => $company->slug,
        ]);

        // Honour a genuine deep link (session expired mid-task), but never
        // the generic launcher — that is precisely what the employee landing
        // is meant to replace, and it is also the URL the guest redirect
        // stashes when someone simply reopens the app.
        $intended = session()->pull('url.intended');

        if ($intended && ! str_contains(parse_url($intended, PHP_URL_PATH) ?? '', '/admin/dashboard')) {
            return redirect()->to($intended);
        }

        return redirect()->to($this->redirectPath($user));
    }
    
    // ════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════════

    private function redirectPath(User $user): string
    {
        // Single source of truth — see homeUrl() in app/Helpers/helpers.php.
        // The old version hand-built 'admin/employee/dashboard', a path that
        // does not exist (the route is /admin/my-dashboard), so employee-only
        // logins on tenant hosts hit a 404.
        return homeUrl();
    }
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirects back to acme.smartbiz.in/admin OR smartbiz.in/acme/admin
        return redirect(tenant_url('admin'))->with('success', 'Logged out successfully.');
    }
}