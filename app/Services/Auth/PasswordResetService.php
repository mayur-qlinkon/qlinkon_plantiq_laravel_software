<?php

namespace App\Services\Auth;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    /** Max failed OTP attempts before the code is invalidated. */
    private const MAX_ATTEMPTS = 3;

    // ════════════════════════════════════════════════════
    //  OTP-BASED FLOW (primary)
    // ════════════════════════════════════════════════════

    /**
     * Generate an OTP, cache it, and send it to the user's email.
     * Silently does nothing if SMTP is not configured — the caller
     * still redirects to the verify page so email enumeration is prevented.
     */
    /**
     * Resolve the single user allowed to reset with this email.
     *
     * Emails are NOT globally unique — the users table is unique on
     * (company_id, email), so two tenants can hold the same address. This runs
     * on a guest route, where Tenantable registers no scope at all, so an
     * unscoped lookup silently returned whichever row had the lowest id: a
     * tenant could reset an identically-named account belonging to another
     * company.
     *
     * Returning null when the address is ambiguous fails closed. Those accounts
     * must be reset by their company admin from the users panel.
     */
    private function resolveUniqueUser(string $email): ?User
    {
        $matches = User::withoutGlobalScopes()
            ->where('email', strtolower($email))
            ->where('status', 'active')
            ->orderBy('id')
            ->limit(2)
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    public function generateAndSendOtp(string $email): void
    {
        $expiryMinutes = (int) (get_system_setting('password_reset_expiry_minutes', 60));
        $otpLength = max(4, min(8, (int) (get_system_setting('otp_length', 6))));
        $appName = get_system_setting('app_name') ?: config('app.name');

        // Cryptographically random numeric OTP, zero-padded to the required length.
        $otp = str_pad(
            (string) random_int(0, (10 ** $otpLength) - 1),
            $otpLength,
            '0',
            STR_PAD_LEFT
        );

        $user = $this->resolveUniqueUser($email);

        // No unique match: send nothing, cache nothing. Returning silently keeps
        // the caller's redirect identical for every address, so this cannot be
        // used to discover which emails exist or which are shared.
        if (! $user) {
            Log::warning('[PasswordReset] No unique account for address', ['email' => $email]);

            return;
        }

        // Cache OTP and reset attempt counter, keyed by user id.
        Cache::put($this->otpKey($user->id), $otp, now()->addMinutes($expiryMinutes));
        Cache::forget($this->attemptsKey($user->id));

        $this->sendOtpEmail($email, $otp, $appName, $expiryMinutes);

        Log::info('[PasswordReset] OTP generated', [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
        ]);
    }

    /**
     * Validate the submitted OTP and reset the user's password.
     *
     * @throws ValidationException on invalid / expired OTP or too many attempts
     */
    public function verifyOtpAndReset(string $email, string $otp, string $password): void
    {
        $user = $this->resolveUniqueUser($email);

        // Resolved the same way as when the OTP was issued. If the account
        // became ambiguous or inactive in between, there is nothing to reset.
        if (! $user) {
            throw ValidationException::withMessages([
                'otp' => 'Invalid or expired OTP. Please request a new one.',
            ]);
        }

        $attemptsKey = $this->attemptsKey($user->id);
        $attempts = (int) Cache::get($attemptsKey, 0);

        if ($attempts >= self::MAX_ATTEMPTS) {
            Cache::forget($this->otpKey($user->id));
            Cache::forget($attemptsKey);

            throw ValidationException::withMessages([
                'otp' => 'Too many incorrect attempts. Please request a new OTP.',
            ]);
        }

        $cached = Cache::get($this->otpKey($user->id));

        if (! $cached || ! hash_equals((string) $cached, (string) $otp)) {
            Cache::increment($attemptsKey);
            $remaining = self::MAX_ATTEMPTS - $attempts - 1;

            throw ValidationException::withMessages([
                'otp' => $remaining > 0
                    ? "Invalid or expired OTP. {$remaining} attempt(s) remaining."
                    : 'Invalid OTP. No attempts remaining — please request a new one.',
            ]);
        }

        // OTP is valid — update the user resolved above. Never re-query by
        // email here: that was the step that crossed tenants.
        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
        ])->save();

        // Revoke all API tokens on password change.
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        event(new PasswordReset($user));

        // Invalidate OTP so it cannot be reused.
        Cache::forget($this->otpKey($user->id));
        Cache::forget($attemptsKey);

        Log::info('[PasswordReset] Password reset via OTP', [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
        ]);
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════════

    /** Cache key for the OTP value. */
    /**
     * Keyed by user id, not email.
     *
     * An email-derived key is shared by every tenant holding that address, so
     * one tenant's OTP unlocked another tenant's account. The user id is the
     * only identifier that is unique across the whole table.
     */
    private function otpKey(int $userId): string
    {
        return 'pwd_otp_reset_u'.$userId;
    }

    /** Cache key for the attempt counter. */
    private function attemptsKey(int $userId): string
    {
        return 'pwd_otp_attempts_u'.$userId;
    }

    /**
     * Apply runtime SMTP config and send the OTP Mailable.
     * Logs an error and returns silently if SMTP is not configured.
     */
    private function sendOtpEmail(
        string $email,
        string $otp,
        string $appName,
        int $expiryMinutes
    ): void {

        $host = get_system_setting('mail_host')
            ?: config('mail.mailers.smtp.host');

        $port = get_system_setting('mail_port')
            ?: config('mail.mailers.smtp.port');

        $username = get_system_setting('mail_username')
            ?: config('mail.mailers.smtp.username');

        $password = get_system_setting('mail_password')
            ?: config('mail.mailers.smtp.password');

        $encryption = get_system_setting('mail_encryption')
            ?: config('mail.mailers.smtp.encryption');

        $fromEmail = get_system_setting('mail_from_email')
            ?: config('mail.from.address');

        $fromName = get_system_setting('mail_from_name')
            ?: config('mail.from.name')
            ?: $appName;

        Config::set('mail.mailers.system_smtp', [
            'transport' => 'smtp',
            'host' => $host,
            'port' => (int) $port,
            'username' => $username,
            'password' => $password,
            'encryption' => $encryption,
        ]);

        Config::set('mail.from.address', $fromEmail);
        Config::set('mail.from.name', $fromName);

        Mail::mailer('system_smtp')
            ->to($email)
            ->send(new OtpMail($otp, $appName, $expiryMinutes));
    }
}
