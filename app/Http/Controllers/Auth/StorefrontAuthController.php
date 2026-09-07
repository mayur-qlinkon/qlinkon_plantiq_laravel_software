<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Enums\Auth\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Services\Auth\SessionContextBuilder;
use Illuminate\Validation\ValidationException;
use Throwable;

class StorefrontAuthController extends Controller
{
    // ════════════════════════════════════════════════════
    //  SHOW FORMS
    // ════════════════════════════════════════════════════

    public function showLoginForm()
    {
        $company = tenant() ?? abort(404, 'Store not found.');

        return view('storefront.auth.login', compact('company'));
    }

    public function showRegisterForm()
    {
        $company = tenant() ?? abort(404, 'Store not found.');

        return view('storefront.auth.register', compact('company'));
    }

    // ════════════════════════════════════════════════════
    //  LOGIN LOGIC
    // ════════════════════════════════════════════════════

    public function login(Request $request)
    {
        $company = tenant() ?? abort(404);

        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 🌟 THE ISOLATION LOCK 🌟
        // Inject company_id and user_type so Auth::attempt ONLY matches 
        // customers of THIS specific tenant.
        // Identity-only credentials. Account state is checked separately so an
        // inactive customer gets a clear message instead of a generic mismatch.
        $credentials = [
            'email'      => $request->email,
            'password'   => $request->password,
            'company_id' => $company->id,
            'user_type'  => UserType::CUSTOMER,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {

            if (Auth::user()->status !== 'active') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'email' => 'Your account is inactive. Please contact the store.',
                ]);
            }

            $request->session()->regenerate();
            app(SessionContextBuilder::class)->build(Auth::user());

            Log::info('[Storefront] Customer Logged In', [
                'user_id' => Auth::id(),
                'company' => $company->slug,
            ]);

            // Using your helper to safely redirect within the tenant's domain mode
            return redirect()->intended(tenant_url('portal/dashboard'));
        }

        Log::warning('[Storefront] Failed Login Attempt', [
            'email'   => $request->email,
            'company' => $company->slug,
        ]);

        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our records for this store.',
        ]);
    }

    // ════════════════════════════════════════════════════
    //  REGISTER LOGIC
    // ════════════════════════════════════════════════════

    public function register(Request $request)
    {
        $company = tenant() ?? abort(404);

        $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'email'    => [
                'required', 'string', 'email', 'max:150',
                // Ensure email is unique ONLY within this specific company
                Rule::unique('users')->where(function ($query) use ($company) {
                    return $query->where('company_id', $company->id)
                                 ->whereNull('deleted_at');
                }),
            ],
        ]);

        DB::beginTransaction();

        try {
            // Create the Customer User Profile.
            // Password is hashed by the model's 'hashed' cast — never call
            // Hash::make() here, it risks double hashing on some versions.
            $user = User::create([
                'company_id' => $company->id,
                'name'       => $request->name,
                'email'      => $request->email,
                'phone'      => $request->phone,
                'password'   => $request->password,
                'status'     => 'active',
                'user_type'  => UserType::CUSTOMER,
            ]);

            // Sync with CRM profile
            $this->linkOrCreateClientProfile($company->id, $user);

            DB::commit();

            Auth::login($user);
            $request->session()->regenerate();
            app(SessionContextBuilder::class)->build($user);

            Log::info('[Storefront] New Customer Registered', [
                'user_id' => $user->id,
                'company' => $company->slug,
            ]);

            return redirect(tenant_url('portal/dashboard'));

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('[Storefront] Customer Registration Failed', [
                'company' => $company->slug,
                'error'   => $e->getMessage(),
            ]);

            return back()->with('error', 'Something went wrong during registration. Please try again.')->withInput();
        }
    }

    // ════════════════════════════════════════════════════
    //  LOGOUT LOGIC
    // ════════════════════════════════════════════════════

    public function logout(Request $request)
    {
        // Resolve the redirect target before the session is destroyed.
        $loginUrl = tenant_url('login');

        Auth::logout();

        // Session may already be gone (expired cookie, cross-host request).
        // Logging out must still succeed, so failures here are non-fatal.
        try {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } catch (Throwable $e) {
            Log::warning('[Storefront] Session invalidation failed during logout.', [
                'error' => $e->getMessage(),
            ]);
        }

        return redirect($loginUrl)->with('success', 'You have been logged out.');
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════════

    /**
     * Checks if a CRM Client already exists for this email/phone.
     * If yes, links the user ID. If no, creates a new CRM profile.
     */
    private function linkOrCreateClientProfile(int $companyId, User $user): void
    {
        // Try to find an existing client by email, or by phone (if phone was provided)
        $client = Client::where('company_id', $companyId)
            ->where(function ($query) use ($user) {
                $query->where('email', $user->email);
                if ($user->phone) {
                    $query->orWhere('phone', $user->phone);
                }
            })->first();

        if ($client) {
            // CRM profile exists! Link the Auth account to it.
            $client->update(['user_id' => $user->id]);

            Log::info('[Storefront] Auth Account linked to existing Client', [
                'user_id'   => $user->id,
                'client_id' => $client->id,
            ]);
        } else {
            // Brand new customer. Build their CRM profile.
            Client::create([
                'company_id'        => $companyId,
                'user_id'           => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'phone'             => $user->phone,
                'registration_type' => 'unregistered',
                'is_active'         => true,
            ]);
        }
    }
}