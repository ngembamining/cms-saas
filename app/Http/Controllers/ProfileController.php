<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Communication\SMSController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /** SMS two-factor setup: code lifetime (minutes) and allowed wrong attempts */
    protected int $twoFactorExpiryMinutes = 10;
    protected int $twoFactorMaxAttempts   = 5;

    /**
     * Show the profile page
     */
    public function index()
    {
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    /**
     * Update user profile
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // 2FA codes are sent to the saved phone number, so it cannot be changed
        // while 2FA is on (otherwise someone on an open session could redirect the codes).
        if ($user->two_factor_enabled
            && trim((string) $request->phone_number) !== trim((string) $user->phone_number)) {
            return redirect()->back()
                ->withErrors(['phone_number' => 'Turn off two-factor authentication before changing your phone number.'])
                ->withInput();
        }

        // Update user without touching timestamps
        $user->full_name = $request->full_name;
        $user->email = $request->email;
        $user->phone_number = $request->phone_number;
        $user->save();

        return redirect()->route('profile.index')
            ->with('success', 'Profile updated successfully!');
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check current password
        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()
                ->withErrors(['current_password' => 'Current password is incorrect'])
                ->withInput();
        }

        // Update password without touching timestamps
        $user->password = Hash::make($request->password);
        $user->password_changed = true;
        $user->save();

        return redirect()->route('profile.index')
            ->with('success', 'Password updated successfully!');
    }

    /**
     * Update user avatar via AJAX
     */
    public function updateAvatar(Request $request)
    {
        try {
            $user = Auth::user();

            // Validate request
            $validator = Validator::make($request->all(), [
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                
                // Validate file
                if (!$file->isValid()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid file upload'
                    ], 400);
                }
                
                // Generate unique filename
                $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
                
                // Store the file
                $path = $file->storeAs('avatars', $filename, 'public');
                
                if (!$path) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to store file'
                    ], 500);
                }
                
                // Delete old avatar if exists
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }
                
                // Update user avatar - store just the path, not the full URL
                $user->avatar = $path;
                $user->save();

                // Return the full URL for display
                $avatarUrl = asset('storage/' . $path);

                return response()->json([
                    'success' => true,
                    'message' => 'Avatar updated successfully!',
                    'avatar' => $avatarUrl
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No avatar file uploaded'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading avatar: ' . $e->getMessage()
            ], 500);
        }
    }

    /* =====================================================================
     |  TWO-FACTOR AUTHENTICATION (SMS) — enable / disable from the profile
     | ===================================================================== */

    /**
     * STEP 1 — send a 6-digit verification code to the user's phone
     */
    public function twoFactorSend(Request $request)
    {
        $user = Auth::user();

        if ($user->two_factor_enabled) {
            return $this->twoFactorFail('Two-factor authentication is already enabled.');
        }

        if (empty($user->phone_number)) {
            return $this->twoFactorFail('Please add your phone number in Profile Information and save it first.');
        }

        // 3 codes per 15 minutes
        $rateKey = '2fa-setup-send:' . $user->id;
        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            $minutes = ceil(RateLimiter::availableIn($rateKey) / 60);
            return $this->twoFactorFail("Too many requests. Please try again in {$minutes} minute(s).");
        }

        $sms   = app(SMSController::class);
        $phone = $sms->formatPhoneNumber($user->phone_number);

        if (!$phone) {
            return $this->twoFactorFail('Your phone number looks invalid. Please correct it in Profile Information.');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        try {
            $sms->sendSms(
                $phone,
                "Ngemba Mining: Your 2-step verification code is {$code}. Valid for {$this->twoFactorExpiryMinutes} minutes. Do not share.",
                'NGEMBA MINE'
            );
        } catch (\Throwable $e) {
            \Log::error('2FA setup SMS failed: ' . $e->getMessage());
            return $this->twoFactorFail('We could not send the SMS right now. Please try again shortly.');
        }

        RateLimiter::hit($rateKey, 900);
        RateLimiter::clear('2fa-setup-attempts:' . $user->id);

        // Keep only a hash of the code (never the code itself)
        $request->session()->put([
            'twofa_setup:hash'    => Hash::make($code),
            'twofa_setup:expires' => now()->addMinutes($this->twoFactorExpiryMinutes)->timestamp,
            'twofa_setup:phone'   => $phone,
        ]);

        return redirect()->route('profile.index')
            ->with('success', 'A verification code has been sent to ' . $this->maskPhone($phone) . '. Enter it below to finish enabling 2FA.');
    }

    /**
     * STEP 2 — check the code and switch 2FA on
     */
    public function twoFactorEnable(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = Auth::user();

        $hash    = $request->session()->get('twofa_setup:hash');
        $expires = $request->session()->get('twofa_setup:expires');

        if (!$hash || !$expires || now()->timestamp > $expires) {
            $this->clearTwoFactorSetup($request);
            return $this->twoFactorFail('This code has expired. Please request a new one.');
        }

        $attemptKey = '2fa-setup-attempts:' . $user->id;
        if (RateLimiter::tooManyAttempts($attemptKey, $this->twoFactorMaxAttempts)) {
            $this->clearTwoFactorSetup($request);
            return $this->twoFactorFail('Too many wrong attempts. Please request a new code.');
        }

        if (!Hash::check($request->code, $hash)) {
            RateLimiter::hit($attemptKey, 300);
            return $this->twoFactorFail('That code is incorrect.');
        }

        RateLimiter::clear($attemptKey);

        // Same layout the existing SMS-2FA rows use: secret = the verified phone number
        $user->two_factor_enabled = true;
        $user->two_factor_method  = 'sms';
        $user->two_factor_secret  = $request->session()->get('twofa_setup:phone');
        $user->save();

        $this->clearTwoFactorSetup($request);

        // They just proved themselves, so don't challenge them again in this session
        $request->session()->put('2fa:verified', true);

        return redirect()->route('profile.index')
            ->with('success', 'Two-factor authentication is now ON. You will receive an SMS code each time you sign in.');
    }

    /**
     * Cancel a pending setup
     */
    public function twoFactorCancel(Request $request)
    {
        $this->clearTwoFactorSetup($request);
        return redirect()->route('profile.index');
    }

    /**
     * Switch 2FA off (needs the current password)
     */
    public function twoFactorDisable(Request $request)
    {
        $user = Auth::user();

        $request->validate(
            ['two_factor_password' => ['required', 'string']],
            ['two_factor_password.required' => 'Enter your current password to turn off 2FA.']
        );

        if (!Hash::check($request->two_factor_password, $user->password)) {
            return redirect()->route('profile.index')
                ->withErrors(['two_factor_password' => 'The password you entered is incorrect.']);
        }

        $user->two_factor_enabled = false;
        $user->two_factor_method  = 'none';
        $user->two_factor_secret  = null;
        $user->save();

        $this->clearTwoFactorSetup($request);

        return redirect()->route('profile.index')
            ->with('success', 'Two-factor authentication has been turned OFF.');
    }

    /* ---------- 2FA helpers ---------- */

    protected function clearTwoFactorSetup(Request $request): void
    {
        $request->session()->forget(['twofa_setup:hash', 'twofa_setup:expires', 'twofa_setup:phone']);
    }

    protected function twoFactorFail(string $message)
    {
        return redirect()->route('profile.index')->withErrors(['two_factor' => $message]);
    }

    protected function maskPhone(string $phone): string
    {
        return strlen($phone) < 7
            ? $phone
            : substr($phone, 0, 5) . str_repeat('*', max(strlen($phone) - 8, 3)) . substr($phone, -3);
    }
}