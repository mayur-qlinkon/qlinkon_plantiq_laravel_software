<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\SessionContextBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PlatformAuthController extends Controller
{
    // ════════════════════════════════════════════════════
    //  SHOW LOGIN FORM (Apex Domain)
    // ════════════════════════════════════════════════════
    public function showLoginForm()
    {
        // Yahan tenant() null hoga kyunki yeh apex domain hai
        return view('auth.login', [
            'company' => null,
        ]);
    }

    // ════════════════════════════════════════════════════
    //  LOGIN LOGIC (Super Admins & Owners ONLY)
    // ════════════════════════════════════════════════════
    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 1. Find user by email (Global Search for "1 email = 1 owner" rule)
        $user = User::with(['company', 'roles.permissions', 'stores'])
            ->where('email', $request->email)
            ->first();

        // 2. Validate Credentials
        if (! $user || ! Hash::check($request->password, $user->password)) {
            $this->throwFailedAuthentication();
        }

        // 3. Status Check
        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => 'Your account is ' . $user->status . '. Please contact support.',
            ]);
        }

        // 🌟 4. THE APEX ISOLATION LOCK 🌟
        // Only allow Super Admins or Company Admins (Owners) to login here.
        if (! $user->isSuperAdmin() && ! $user->isCompanyAdmin()) {
            Log::warning('[Platform Auth] Unauthorized Access Attempt', [
                'email' => $request->email,
                'role'  => 'Employee/Customer',
            ]);

            throw ValidationException::withMessages([
                'email' => 'Staff and Customers must log in through their specific store URL.',
            ]);
        }

        // 5. Authenticate. Auth::login() already generates + persists the
        // remember token when $remember is true — do not regenerate it
        // afterwards (that was creating a cookie/DB token mismatch that
        // silently broke Remember Me on every session expiry).
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // 6. Setup Session Context (Only relevant for Owners, Super Admins may not have a company)
        if ($user->company_id) {
            app(SessionContextBuilder::class)->build($user);
        }

        Log::info('[Platform Auth] Global User Logged In', [
            'user_id'   => $user->id,
            'user_type' => $user->user_type->value,
        ]);

        return redirect()->intended($this->redirectPath($user));
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════════    

    private function redirectPath(User $user): string
    {
        // Super Admins go to their master platform dashboard
        if ($user->isSuperAdmin()) {
            // Check if you have a platform.dashboard route, otherwise fallback
            return route('platform.dashboard'); 
        }

        // Owners stay on the apex domain but go to their resolved landing
        // screen. Single source of truth — see homeUrl() in helpers.php.
        return homeUrl();
    }

    private function throwFailedAuthentication(): void
    {
        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);
    }
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirects back to smartbiz.in/admin
        return redirect()->route('admin.login')->with('success', 'Logged out successfully.');
    }
}