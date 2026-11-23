<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use App\Http\Controllers\Communication\SMSController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class TwoFactorController extends Controller
{
    protected TwoFactorService $twoFactor;

    public function __construct(TwoFactorService $twoFactor)
    {
        $this->twoFactor = $twoFactor;
    }

    /**
     * ===========================================================
     * CHALLENGE — shown after login when 2FA is required
     * ===========================================================
     */
    public function showChallenge(Request $request)
    {
        $userId = $request->session()->get('2fa:user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (!$user) {
            $request->session()->forget('2fa:user_id');
            return redirect()->route('login');
        }

        $method = $user->two_factor_method;

        // For SMS method, auto-send a fresh code on page load
        if ($method === 'sms') {
            $this->sendSmsCode($user);
        }

        return view('auth.2fa.challenge', compact('user', 'method'));
    }

    /**
     * ===========================================================
     * VERIFY — validates the submitted code
     * ===========================================================
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|min:6|max:10',
        ]);

        $userId = $request->session()->get('2fa:user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (!$user) {
            $request->session()->forget('2fa:user_id');
            return redirect()->route('login');
        }

        // Rate limiting (5 attempts per 5 minutes)
        $key = '2fa-attempt:' . $user->id . ':' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'code' => "Too many attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $code = trim($request->input('code'));
        $valid = false;

        // Try the user's configured method first
        if ($user->two_factor_method === 'authenticator' && $user->two_factor_secret) {
            $valid = $this->twoFactor->verifyTotp($user->two_factor_secret, $code);
        } elseif ($user->two_factor_method === 'sms') {
            $valid = $this->twoFactor->verifySmsCode($code);
        }

        // Fallback: try recovery code (works for any method)
        if (!$valid) {
            $valid = $this->twoFactor->verifyRecoveryCode($user, $code);
        }

        if (!$valid) {
            RateLimiter::hit($key, 300);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'code' => __('The provided two-factor code is invalid.'),
            ]);
        }

        RateLimiter::clear($key);

        // SUCCESS → actually log the user in now
        $remember = $request->session()->pull('2fa:remember', false);
        Auth::login($user, $remember);

        // Clean up 2FA challenge session keys, mark verified
        $request->session()->forget(['2fa:user_id', '2fa:remember', '2fa:method', '2fa:sms_code', '2fa:sms_code_expires']);
        $request->session()->put('2fa:verified', true);
        $request->session()->regenerate();

        return redirect()->intended(\App\Providers\RouteServiceProvider::HOME);
    }

    /**
     * ===========================================================
     * RESEND SMS CODE
     * ===========================================================
     */
    public function resendSms(Request $request)
    {
        $userId = $request->session()->get('2fa:user_id');
        $user = $userId ? User::find($userId) : null;

        if (!$user || $user->two_factor_method !== 'sms') {
            return redirect()->route('login');
        }

        $this->sendSmsCode($user);

        return back()->with('status', 'A new code has been sent to your phone.');
    }

    /**
     * ===========================================================
     * SETUP — enable 2FA for the logged-in user
     * ===========================================================
     */
    public function setup(Request $request)
    {
        $user = Auth::user();
        $method = $request->query('method', 'authenticator');

        if ($method === 'authenticator') {
            // Generate a fresh secret each time setup is opened (until confirmed)
            $secret = $this->twoFactor->generateSecret();
            $qrUrl = $this->twoFactor->getQrCodeUrl($user, $secret);

            // Store in session until user confirms
            $request->session()->put('2fa:setup_secret', $secret);
            $request->session()->put('2fa:setup_method', 'authenticator');

            return view('auth.2fa.setup-authenticator', compact('user', 'secret', 'qrUrl'));
        }

        // SMS setup
        if ($method === 'sms') {
            if (empty($user->phone_number)) {
                return redirect()->route('profile.index')
                    ->with('error', 'Please add a phone number to your profile before enabling SMS 2FA.');
            }

            $request->session()->put('2fa:setup_method', 'sms');

            // Send a test code
            $this->sendSmsCode($user, true);

            return view('auth.2fa.setup-sms', compact('user'));
        }

        abort(400, 'Invalid 2FA method.');
    }

    /**
     * Confirm authenticator setup by verifying the first code.
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        $secret = $request->session()->get('2fa:setup_secret');

        if (!$secret) {
            return redirect()->route('2fa.setup');
        }

        if (!$this->twoFactor->verifyTotp($secret, $request->code)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'code' => __('That code is not valid. Please try again.'),
            ]);
        }

        // Generate recovery codes
        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $user->two_factor_secret = $secret;
        $user->two_factor_method = 'authenticator';
        $user->two_factor_enabled = true;
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
        $user->save();

        $request->session()->forget(['2fa:setup_secret', '2fa:setup_method']);
        $request->session()->flash('2fa:recovery_codes', $recoveryCodes);

        return redirect()->route('2fa.recovery-codes')
            ->with('success', 'Two-factor authentication has been enabled.');
    }

    /**
     * Confirm SMS setup by verifying the test code.
     */
    public function confirmSms(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();

        if (!$this->twoFactor->verifySmsCode($request->code)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'code' => __('That code is not valid or has expired.'),
            ]);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $user->two_factor_method = 'sms';
        $user->two_factor_enabled = true;
        $user->two_factor_confirmed_at = now();
        $user->two_factor_secret = $user->phone_number;
        $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
        $user->save();

        $request->session()->forget(['2fa:setup_method']);
        $request->session()->flash('2fa:recovery_codes', $recoveryCodes);

        return redirect()->route('2fa.recovery-codes')
            ->with('success', 'SMS two-factor authentication has been enabled.');
    }

    /**
     * ===========================================================
     * RECOVERY CODES page (shown once after enabling)
     * ===========================================================
     */
    public function showRecoveryCodes(Request $request)
    {
        $codes = $request->session()->get('2fa:recovery_codes');
        if (!$codes) {
            return redirect()->route('dashboard');
        }

        return view('auth.2fa.recovery-codes', compact('codes'));
    }

    /**
     * ===========================================================
     * DISABLE 2FA
     * ===========================================================
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();

        if (!\Hash::check($request->password, $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'password' => __('Your password is incorrect.'),
            ]);
        }

        $user->two_factor_secret = null;
        $user->two_factor_method = 'none';
        $user->two_factor_enabled = false;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        return redirect()->route('profile.index')
            ->with('success', 'Two-factor authentication has been disabled.');
    }

    /**
     * ===========================================================
     * INTERNAL — send an SMS code via the existing SMSController
     * ===========================================================
     */
    protected function sendSmsCode(User $user, bool $isSetup = false): void
    {
        if (empty($user->phone_number)) {
            \Log::warning('Cannot send 2FA SMS — user ' . $user->id . ' has no phone number.');
            return;
        }

        $code = $this->twoFactor->generateSmsCode();

        session([
            '2fa:sms_code' => $code,
            '2fa:sms_code_expires' => now()->addMinutes(5)->timestamp,
        ]);

        $message = $isSetup
            ? "Ngemba Mining: Your 2FA setup code is {$code}. Valid for 5 minutes. Do not share."
            : "Ngemba Mining: Your login code is {$code}. Valid for 5 minutes. Do not share.";

        try {
            $sms = app(SMSController::class);
            $phone = $sms->formatPhoneNumber($user->phone_number);
            if ($phone) {
                $sms->sendSms($phone, $message, 'NGEMBA MINE');
                \Log::info('2FA SMS sent to user ' . $user->id . ' at ' . $phone);
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to send 2FA SMS: ' . $e->getMessage());
        }
    }
}