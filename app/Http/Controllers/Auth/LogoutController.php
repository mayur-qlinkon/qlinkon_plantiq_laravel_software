<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    // -------------------------------------------------------
    // POST /logout — Context-Aware Smart Logout
    // -------------------------------------------------------
    public function destroy(Request $request): RedirectResponse
    {
        // 1. Capture user context BEFORE logging out
        $user = $request->user();
        $isCustomer = $user ? $user->isCustomer() : false;
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;
        $tenant = tenant(); 

        // Invalidate Remember Me token
        if ($user) {
            $user->setRememberToken(Str::random(60));
            $user->save();
        }
        
        // 2. Perform standard logout & clear session
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 3. Smart Redirect Logic
        $company = $user ? $user->company : null;
        
        // Scenario A: It was a Storefront Customer
        if ($isCustomer) {
            return redirect($tenant ? tenant_url('login') : url(($company->slug ?? '') . '/login'));
        }

        // Scenario B: Any internal user — owner or team member
        if ($user && ! $isSuperAdmin) {
            return redirect($tenant ? tenant_url('admin') : url(($company->slug ?? '') . '/admin'))
                ->with('success', 'You have been logged out.');
        }

        // Scenario C: It was a Super Admin on the Apex Domain
        return redirect()->route('admin.login')->with('success', 'You have been logged out.');
    }
}