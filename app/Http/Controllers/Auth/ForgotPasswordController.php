<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function __construct(private readonly PasswordResetService $passwordResetService) {}

    // ──────────────────────────────────────────────────
    //  STEP 1 — Email entry
    // ──────────────────────────────────────────────────

    /** GET /forgot-password */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /** POST /forgot-password — validate email, generate OTP, redirect to verify */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            // No exists check. It confirmed which addresses have accounts, and
            // the service already stays silent when there is no unique match —
            // so every submission must look identical from outside.
            'email' => ['required', 'email'],
        ]);

        // Two limiters, because they stop different attacks.
        //
        // Removing the exists check means every submission now reaches a DB
        // lookup and possibly an SMTP send. Per-IP caps the address sweep that
        // enumeration would otherwise become; per-email stops one account being
        // mail-bombed from many addresses. Shared hosting SMTP quotas are small
        // enough that either one could exhaust a day's sends.
        $ipKey = 'pwd-reset-ip:'.$request->ip();
        $emailKey = 'pwd-reset-email:'.sha1(strtolower($request->email));

        foreach ([$ipKey => 5, $emailKey => 3] as $key => $max) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $seconds = RateLimiter::availableIn($key);

                throw ValidationException::withMessages([
                    'email' => "Too many reset requests. Please try again in {$seconds} second(s).",
                ]);
            }
        }

        RateLimiter::hit($ipKey, 900);    // 5 per 15 minutes per IP
        RateLimiter::hit($emailKey, 900); // 3 per 15 minutes per address

        try {
            $this->passwordResetService->generateAndSendOtp($request->email);
        } catch (\Throwable $e) {
            // OTP is still cached even if the mail send fails.
            // Log the error but don't surface it — prevents email enumeration.
            Log::error('[ForgotPassword] OTP send error: '.$e->getMessage());
        }

        // Store email in session (not URL) to prevent parameter tampering.
        session(['pwd_reset_email' => strtolower($request->email)]);

        return redirect()
            ->route('password.verify')
            ->with('status', 'A 6-digit OTP has been sent to your email address.');
    }

    // ──────────────────────────────────────────────────
    //  STEP 2 — OTP + new password
    // ──────────────────────────────────────────────────

    /** GET /forgot-password/verify */
    public function showVerify(Request $request): View|RedirectResponse
    {
        if (! session('pwd_reset_email')) {
            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Please enter your email to start the reset process.']);
        }

        return view('auth.verify-otp', [
            'email' => session('pwd_reset_email'),
        ]);
    }

    /** POST /forgot-password/verify — validate OTP + update password */
    public function storeVerify(Request $request): RedirectResponse
    {
        $email = session('pwd_reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        $otpLength = (int) get_system_setting('otp_length', 6);

        $request->validate([
            'otp' => ['required', 'digits:'.$otpLength],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
        ], [
            'otp.required' => 'Please enter the OTP sent to your email.',
            'otp.digits' => "OTP must be exactly {$otpLength} digits.",
        ]);

        // The service caps attempts per account, but that counter resets with
        // every newly requested OTP. This one does not, so a 6-digit code
        // cannot be walked through by cycling request-OTP and guess-OTP.
        $verifyKey = 'pwd-verify-ip:'.$request->ip();

        if (RateLimiter::tooManyAttempts($verifyKey, 10)) {
            throw ValidationException::withMessages([
                'otp' => 'Too many attempts. Please try again later.',
            ]);
        }

        RateLimiter::hit($verifyKey, 900);

        $this->passwordResetService->verifyOtpAndReset($email, $request->otp, $request->password);

        // Password changed — clear the throttle for a legitimate user.
        RateLimiter::clear($verifyKey);

        session()->forget('pwd_reset_email');

        // New session id after a credential change, so anything holding the
        // pre-reset session cannot ride it forward.
        $request->session()->regenerate();

        return redirect()
            ->route('admin.login')
            ->with('success', 'Password reset successfully. Please log in.');
    }
}
