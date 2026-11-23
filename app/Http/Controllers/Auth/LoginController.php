<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | Authenticates users for the application. Re-implemented without the
    | legacy AuthenticatesUsers trait, which was removed in Laravel 11.
    |
    | Flow:
    |   1. Credentials are validated WITHOUT logging in.
    |   2. If password_changed is false  -> user is sent to the create-new-
    |      password page (the same page used by the SMS reset flow). They are
    |      logged in only AFTER saving the new password.
    |   3. If 2FA is enabled -> 2FA challenge page.
    |   4. Otherwise -> logged in, dashboard.
    |
    */

    protected string $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // Changed validation from 'email' to 'username'
        $credentials = $request->validate([
            'username' => ['required', 'string'],  // Changed from 'email'
            'password' => ['required', 'string'],
        ]);

        // Validate credentials WITHOUT logging the user in yet.
        if (!Auth::validate($credentials)) {
            throw ValidationException::withMessages([
                'username' => __('auth.failed'),  // Changed from 'email'
            ]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::getProvider()->retrieveByCredentials($credentials);
        $remember = $request->boolean('remember');

        // ------------------------------------------------------------------
        // First-login branch: password has never been changed
        // ------------------------------------------------------------------
        // The user proved they know the temporary password, so we skip the
        // phone/OTP steps and send them straight to the create-new-password
        // page. They are NOT logged in yet; SmsPasswordResetController logs
        // them in (and applies 2FA if enabled) once the new password is saved.
        // ------------------------------------------------------------------
        if (! $user->password_changed) {
            // Fresh session id, then set the same flags the OTP step would set
            $request->session()->regenerate();
            $request->session()->put([
                'sms_reset:user_id'     => $user->id,
                'sms_reset:verified'    => true,
                'sms_reset:verified_at' => now()->timestamp,
                'sms_reset:forced'      => $user->id,   // marks "first-login" flow for this user
                'sms_reset:remember'    => $remember,
            ]);

            return redirect()
                ->route('password.sms.reset.form')
                ->with('status', 'For your security, please create a new password to continue.');
        }

        // ------------------------------------------------------------------
        // Two-Factor Authentication branch
        // ------------------------------------------------------------------
        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('2fa:user_id', $user->id);
            $request->session()->put('2fa:remember', $remember);
            $request->session()->put('2fa:method', $user->two_factor_method);

            // Regenerate session ID to prevent fixation during the
            // half-authenticated state.
            $request->session()->regenerate();

            return redirect()->route('2fa.challenge');
        }

        // ------------------------------------------------------------------
        // No 2FA — log the user in directly (original behavior)
        // ------------------------------------------------------------------
        Auth::login($user, $remember);
        $request->session()->regenerate();

        // Mark 2FA as "satisfied" so the 2fa middleware allows access.
        $request->session()->put('2fa:verified', true);

        return redirect()->intended($this->redirectTo);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}