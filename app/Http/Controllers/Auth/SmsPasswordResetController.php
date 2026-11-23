<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Communication\SMSController;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class SmsPasswordResetController extends Controller
{
    /**
     * How long a code stays valid (minutes).
     */
    protected int $expiryMinutes = 10;

    /**
     * How many code-entry attempts are allowed per token.
     */
    protected int $maxAttempts = 5;

    /**
     * ===========================================================
     * STEP 1 — Send a 6-digit reset code via SMS
     * ===========================================================
     */
    public function sendCode(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:20',
        ]);

        // A normal "forgot password" request is never a first-login flow
        $request->session()->forget(['sms_reset:forced', 'sms_reset:remember', 'sms_reset:verified', 'sms_reset:verified_at']);

        $phone = $this->normalizePhone($request->phone_number);

        // Rate limit per phone number (3 requests per 15 min)
        $rateKey = 'sms-reset-request:' . $phone;
        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            $seconds = RateLimiter::availableIn($rateKey);
            $minutes = ceil($seconds / 60);
            throw ValidationException::withMessages([
                'phone_number' => "Too many reset requests. Please try again in {$minutes} minute(s).",
            ]);
        }

        // Find the user by phone (try a few formats)
        $user = $this->findUserByPhone($phone);

        if (!$user) {
            RateLimiter::hit($rateKey, 900);
            // Security: don't reveal whether a phone is registered
            // Pretend success to prevent enumeration
            return redirect()
                ->route('password.sms.verify.form')
                ->with('status', 'If the phone number is registered, a reset code has been sent.');
        }

        // Generate a 6-digit code
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store a hash of the code (never plaintext)
        $user->reset_token = Hash::make($code);
        $user->reset_expires = now()->addMinutes($this->expiryMinutes);
        $user->save();

        // Send SMS
        $this->sendCodeSms($user, $code);

        RateLimiter::hit($rateKey, 900);
        // Reset the code-entry attempts
        RateLimiter::clear('sms-reset-attempts:' . $user->id);

        // Store user_id in session so the verify page knows whose code to check
        session([
            'sms_reset:user_id'    => $user->id,
            'sms_reset:phone_hint' => $this->maskPhone($phone),
        ]);

        return redirect()
            ->route('password.sms.verify.form')
            ->with('status', "A reset code has been sent to {$this->maskPhone($phone)}. It expires in {$this->expiryMinutes} minutes.");
    }

    /**
     * ===========================================================
     * STEP 2 — Show the code-entry form
     * ===========================================================
     */
    public function showVerifyForm(Request $request)
    {
        // If the user hasn't requested a code yet, still show the form
        // (they can go back to /password/forget and click "Send via SMS")
        return view('auth.passwords.sms-verify');
    }

    /**
     * ===========================================================
     * STEP 3 — Verify the code, then show new password form
     * ===========================================================
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $userId = $request->session()->get('sms_reset:user_id');
        if (!$userId) {
            return redirect()
                ->route('password.request')
                ->with('error', 'Your session has expired. Please request a new code.');
        }

        $user = User::find($userId);
        if (!$user) {
            $request->session()->forget(['sms_reset:user_id', 'sms_reset:phone_hint']);
            return redirect()
                ->route('password.request')
                ->with('error', 'Account not found. Please request a new code.');
        }

        // Rate limit code attempts
        $attemptKey = 'sms-reset-attempts:' . $user->id;
        if (RateLimiter::tooManyAttempts($attemptKey, $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($attemptKey);
            throw ValidationException::withMessages([
                'code' => "Too many attempts. Please request a new code. (Retry in {$seconds}s)",
            ]);
        }

        // Validate
        if (empty($user->reset_token) || empty($user->reset_expires)) {
            RateLimiter::hit($attemptKey, 300);
            throw ValidationException::withMessages([
                'code' => 'No active reset code found. Please request a new one.',
            ]);
        }

        if (now()->greaterThan($user->reset_expires)) {
            RateLimiter::hit($attemptKey, 300);
            throw ValidationException::withMessages([
                'code' => 'This code has expired. Please request a new one.',
            ]);
        }

        if (!Hash::check($request->code, $user->reset_token)) {
            RateLimiter::hit($attemptKey, 300);
            throw ValidationException::withMessages([
                'code' => 'That code is incorrect.',
            ]);
        }

        // SUCCESS → store a "verified" flag in session for the final step
        RateLimiter::clear($attemptKey);

        $request->session()->put('sms_reset:verified', true);
        $request->session()->put('sms_reset:verified_at', now()->timestamp);

        return redirect()->route('password.sms.reset.form');
    }

    /**
     * ===========================================================
     * STEP 3b — Show new password form (after verify)
     * ===========================================================
     */
    public function showResetForm(Request $request)
    {
        if (! $request->session()->get('sms_reset:verified')) {
            return redirect()
                ->route('password.sms.verify.form')
                ->with('error', 'Please verify your reset code first.');
        }

        return view('auth.passwords.sms-reset');
    }

    /**
     * ===========================================================
     * STEP 4 — Save the new password
     * ===========================================================
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Must have passed step 3
        if (! $request->session()->get('sms_reset:verified')) {
            return redirect()
                ->route('password.request')
                ->with('error', 'Your session has expired. Please request a new code.');
        }

        // Optional: expire the verification if it's older than 15 min
        $verifiedAt = $request->session()->get('sms_reset:verified_at', 0);
        if (now()->timestamp - $verifiedAt > 900) {
            $request->session()->forget(['sms_reset:user_id', 'sms_reset:phone_hint', 'sms_reset:verified', 'sms_reset:verified_at', 'sms_reset:forced', 'sms_reset:remember']);
            return redirect()
                ->route('password.request')
                ->with('error', 'Verification expired. Please request a new code.');
        }

        $userId = $request->session()->get('sms_reset:user_id');
        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('login');
        }

        // FIRST-LOGIN FLOW: LoginController validated the user's (temporary) password
        // but password_changed is false, so it did NOT log them in. It stored the
        // user's id in 'sms_reset:forced'. Only honour it for that same user.
        $forced   = $request->session()->get('sms_reset:forced') === $user->id;
        $remember = (bool) $request->session()->get('sms_reset:remember', false);

        // The new password must differ from the temporary one
        if ($forced && Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'The new password must be different from your current password.',
            ]);
        }

        // Save new password
        $user->password = Hash::make($request->password);
        $user->password_changed = true;   // marks that the user has changed their initial password
        $user->reset_token = null;
        $user->reset_expires = null;
        $user->save();

        // Clean up session
        $request->session()->forget([
            'sms_reset:user_id',
            'sms_reset:phone_hint',
            'sms_reset:verified',
            'sms_reset:verified_at',
            'sms_reset:forced',
            'sms_reset:remember',
        ]);

        // First-login flow: finish the login that LoginController held back
        if ($forced) {
            // Still honour two-factor authentication
            if ($user->hasTwoFactorEnabled()) {
                $request->session()->regenerate();
                $request->session()->put('2fa:user_id', $user->id);
                $request->session()->put('2fa:remember', $remember);
                $request->session()->put('2fa:method', $user->two_factor_method);

                return redirect()->route('2fa.challenge');
            }

            Auth::login($user, $remember);
            $request->session()->regenerate();
            $request->session()->put('2fa:verified', true);

            return redirect()
                ->intended(RouteServiceProvider::HOME)
                ->with('success', 'Your new password has been saved. Welcome!');
        }

        return redirect()
            ->route('login')
            ->with('status', 'Your password has been reset successfully. Please sign in with your new password.');
    }

    /**
     * ===========================================================
     * RESEND — sends a fresh code (same as sendCode, but reuses
     * the phone from session)
     * ===========================================================
     */
    public function resendCode(Request $request)
    {
        $userId = $request->session()->get('sms_reset:user_id');
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            return redirect()->route('password.request')
                ->with('error', 'Please start over and request a new code.');
        }

        $rateKey = 'sms-reset-request:' . $user->phone_number;
        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            $seconds = RateLimiter::availableIn($rateKey);
            $minutes = ceil($seconds / 60);
            throw ValidationException::withMessages([
                'code' => "Please wait {$minutes} minute(s) before requesting another code.",
            ]);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->reset_token = Hash::make($code);
        $user->reset_expires = now()->addMinutes($this->expiryMinutes);
        $user->save();

        $this->sendCodeSms($user, $code);

        RateLimiter::hit($rateKey, 900);

        return back()->with('status', 'A new code has been sent.');
    }

    /**
     * ===========================================================
     * HELPERS
     * ===========================================================
     */

    /**
     * Normalize a phone number to a compact format (digits only).
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone);
        // Strip leading country code 255 if present, keep it simple
        if (str_starts_with($phone, '255')) {
            $phone = substr($phone, 3);
        }
        return $phone;
    }

    /**
     * Find a user by phone number — tries multiple common formats.
     */
    protected function findUserByPhone(string $normalized): ?User
    {
        $candidates = [
            $normalized,
            '0' . $normalized,
            '+255' . $normalized,
            '255' . $normalized,
        ];

        return User::whereIn('phone_number', array_unique($candidates))
            ->whereNotNull('phone_number')
            ->first();
    }

    /**
     * Mask a phone number for display (e.g., 0712***456).
     */
    protected function maskPhone(string $phone): string
    {
        if (strlen($phone) < 6) {
            return $phone;
        }
        return substr($phone, 0, 3)
            . str_repeat('*', max(strlen($phone) - 6, 3))
            . substr($phone, -3);
    }

    /**
     * Send the reset code via your existing SMSController.
     */
    protected function sendCodeSms(User $user, string $code): void
    {
        // Resolve the template from sms_templates (with fallback)
        $template = DB::table('sms_templates')
            ->where('name', 'password_reset_code')
            ->first();

        if ($template) {
            $message = str_replace(
                ['{code}', '{name}'],
                [$code, $user->full_name ?? $user->username],
                $template->message
            );
        } else {
            $message = "Ngemba Mining: Your password reset code is {$code}. Valid for {$this->expiryMinutes} minutes. Do not share.";
        }

        try {
            $sms = app(SMSController::class);
            $phone = $sms->formatPhoneNumber($user->phone_number);
            if ($phone) {
                $sms->sendSms($phone, $message, 'NGEMBA MINE');
                \Log::info("Password reset SMS sent to user {$user->id} ({$phone})");
            } else {
                \Log::warning("Could not format phone for user {$user->id}: {$user->phone_number}");
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to send password reset SMS: ' . $e->getMessage());
        }
    }
}