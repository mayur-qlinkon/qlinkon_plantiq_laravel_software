<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login for the Flutter app.
     *
     * The app talks to the central root domain, so — unlike the web login —
     * there is no host or slug to resolve a tenant from. Company is therefore
     * established from the credentials themselves:
     *
     *   1. If the client sent a company hint (company_id from the picker, or
     *      a company slug/subdomain typed by the user), the lookup is scoped
     *      to that company and behaves exactly like the web login.
     *   2. Otherwise the email is looked up across every active company, and
     *      the password decides. One match logs straight in.
     *   3. If the same email + password exists in more than one company, a 409
     *      is returned with the list so the app can show a company picker and
     *      retry with company_id.
     *
     * The company list is only ever disclosed AFTER a correct password, so
     * this endpoint cannot be used to enumerate which emails exist where.
     *
     * The eligibility rules themselves are unchanged from the web login:
     * super admins, customers and non-active users are all refused.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'company' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'integer'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $loginId = trim($request->input('email'));
        $password = $request->input('password');

        // ── 1. Company hint, if the client gave one ────────────────────
        $hintProvided = $request->filled('company_id') || $request->filled('company');
        $company = $this->resolveCompanyHint($request);

        if ($hintProvided && ! $company) {
            return response()->json([
                'status' => 'error',
                'message' => 'Company not found.',
            ], 404);
        }

        // ── 2. Employee-code login needs a company ─────────────────────
        // Employee codes are only unique within a company — EMP001 exists in
        // most of them — so a global lookup on one is meaningless. An email
        // is at least globally distinctive enough for the password to settle.
        if (! $company && ! filter_var($loginId, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'status' => 'company_required',
                'message' => 'Please enter your company code to sign in with an employee code.',
                'companies' => [],
            ], 409);
        }

        // ── 3. Candidates, then let the password decide ────────────────
        $matches = $this->findCandidates($loginId, $company)
            ->filter(fn (User $u) => Hash::check($password, $u->password))
            ->values();

        if ($matches->isEmpty()) {
            Log::warning('[API Login] Failed Login Attempt', [
                'login_id' => $loginId,
                'company' => $company?->slug,
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match any account.',
            ]);
        }

        if ($matches->count() > 1) {
            return response()->json([
                'status' => 'company_required',
                'message' => 'This login is used in more than one company. Please choose one.',
                'companies' => $matches->map(fn (User $u) => [
                    'id' => $u->company->id,
                    'name' => $u->company->name,
                    'slug' => $u->company->slug,
                ])->values(),
            ], 409);
        }

        $user = $matches->first();

        // ── 4. Eligibility — same rules as the web login ───────────────
        if ($user->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'email' => 'Super Admins cannot use this app.',
            ]);
        }

        if ($user->isCustomer() || $user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => 'Your account is inactive or not authorized for this app.',
            ]);
        }

        $deviceName = $request->input('device_name', 'flutter-app');
        $token = $user->createToken($deviceName)->plainTextToken;

        Log::info('[API Login] User Logged In', [
            'user_id' => $user->id,
            'user_type' => $user->user_type->value,
            'company' => $user->company->slug,
        ]);

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => $this->formatMe($user),
        ]);
    }

    /**
     * Resolve the company the client pointed us at, if any.
     *
     * company_id is what the picker sends back after a 409; company is the
     * code a user may type on the login screen, matched against either the
     * slug or the subdomain since tenants know their own by both names.
     * Falls back to tenant() so that hitting a tenant subdomain or custom
     * domain still scopes the login the way the web app would.
     */
    private function resolveCompanyHint(Request $request): ?Company
    {
        if ($request->filled('company_id')) {
            return Company::where('id', (int) $request->input('company_id'))
                ->where('is_active', true)
                ->first();
        }

        if ($request->filled('company')) {
            $code = strtolower(trim($request->input('company')));

            return Company::where('is_active', true)
                ->where(fn ($q) => $q->where('slug', $code)->orWhere('subdomain', $code))
                ->first();
        }

        return tenant();
    }

    /**
     * Users whose email or employee code matches, in active companies only.
     *
     * NOTE: the Tenantable global scope is not registered on User here —
     * it only boots when a user is already authenticated, which by
     * definition is not the case during login. The company condition below
     * is therefore the real one, not a convenience on top of a scope.
     *
     * The limit is a safety valve: each candidate costs one bcrypt check,
     * so an email shared across an implausible number of companies must not
     * turn a login into a CPU sink.
     */
    private function findCandidates(string $loginId, ?Company $company): Collection
    {
        return User::query()
            ->with('company')
            ->whereHas('company', fn ($q) => $q->where('is_active', true))
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->where(function ($q) use ($loginId) {
                $q->where('email', $loginId)
                    ->orWhereHas('employee', fn ($eq) => $eq->where('employee_code', $loginId));
            })
            ->limit(10)
            ->get();
    }

    /**
     * Revoke only the token used for this request — other devices stay
     * logged in.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out.',
        ]);
    }

    /**
     * Everything the app needs right after login (or on cold start with a
     * saved token) to decide what to show: identity, assigned stores (for
     * the store picker / X-Store-Id header), and which of the two modules
     * this app cares about are actually licensed for this user.
     */
    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'user' => $this->formatMe($request->user()),
        ]);
    }

    private function formatMe(User $user): array
    {
        $user->loadMissing(['stores', 'company']);

        // has_module() / has_permission() read Auth::user() internally.
        // During login() nothing has authenticated through the guard yet
        // (we did a manual Hash::check, not Auth::login()), so bind it
        // explicitly — harmless no-op when called from me(), where
        // auth:sanctum already did this via the request lifecycle.
        Auth::setUser($user);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'user_type' => $user->user_type->value,
            'company' => [
                'id' => $user->company->id,
                'name' => $user->company->name,
            ],
            'stores' => $user->isCompanyAdmin()
                ? Store::where('company_id', $user->company_id)->get(['id', 'name'])
                : $user->stores->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
            'modules' => [
                'pos' => has_module('pos'),
                'label_printing' => has_module('label_printing'),
            ],
            'permissions' => [
                'pos.access' => has_permission('pos.access'),
                'labels.print' => has_permission('labels.print'),
            ],
        ];
    }
}