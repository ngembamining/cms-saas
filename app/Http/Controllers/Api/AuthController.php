<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\AuthRequest;
use App\Http\Requests\PasswordChangeRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseController
{
    /**
     * Authenticate a user for the mobile app.
     *
     * Returns one of three states in `data.status`:
     *   - "authenticated"            -> full access_token issued
     *   - "password_reset_required"  -> reset_token (scoped, 15 min) issued
     *   - "2fa_required"             -> 2fa_token   (scoped, 10 min) issued
     */
    public function login(AuthRequest $request): JsonResponse
    {
        // Match the web controller: authenticate by username, not email.
        $credentials = $request->only(['username', 'password']);

        // Validate credentials WITHOUT logging in.
        if (! Auth::validate($credentials)) {
            return $this->failedResponse('Invalid credentials!', 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::getProvider()->retrieveByCredentials($credentials);

        // Guard against missing user (shouldn't happen after validate, but be safe).
        if (! $user) {
            return $this->failedResponse('Invalid credentials!', 401);
        }

        $deviceName = $request->input('device_name', 'mobile-app');

        // ------------------------------------------------------------------
        // Branch 1: first login — password has never been changed.
        // ------------------------------------------------------------------
        // The user proved they know the temporary password, but must set a
        // new one before getting a full token. We issue a short-lived,
        // ability-scoped token that ONLY the password-reset endpoint accepts.
        // ------------------------------------------------------------------
        if (! $user->password_changed) {
            // Revoke any stale reset tokens for this user to avoid buildup.
            $user->tokens()->where('name', 'password-reset')->delete();

            $resetToken = $user->createToken(
                'password-reset',
                ['password:reset'],
                now()->addMinutes(15)
            )->plainTextToken;

            return $this->successResponse([
                'status'      => 'password_reset_required',
                'reset_token' => $resetToken,
                'token_type'  => 'Bearer',
                'message'     => 'For your security, please create a new password to continue.',
            ]);
        }

        // ------------------------------------------------------------------
        // Branch 2: Two-Factor Authentication required.
        // ------------------------------------------------------------------
        if ($user->hasTwoFactorEnabled()) {
            $user->tokens()->where('name', '2fa-pending')->delete();

            $twoFactorToken = $user->createToken(
                '2fa-pending',
                ['2fa:challenge'],
                now()->addMinutes(10)
            )->plainTextToken;

            return $this->successResponse([
                'status'    => '2fa_required',
                '2fa_token' => $twoFactorToken,
                'method'    => $user->two_factor_method, // 'sms' | 'totp' | 'email'
                'message'   => 'Enter your two-factor authentication code.',
            ]);
        }

        // ------------------------------------------------------------------
        // Branch 3: No 2FA, password is fine — issue full access token.
        // ------------------------------------------------------------------
        // Revoke old full-access tokens for this device to avoid duplicates.
        $user->tokens()
            ->where('name', $deviceName)
            ->delete();

        $accessToken = $user->createToken($deviceName)->plainTextToken;

        return $this->successResponse([
            'status'       => 'authenticated',
            'access_token' => $accessToken,
            'token_type'   => 'Bearer',
            'user'         => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'email'    => $user->email,
            ],
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        if ($user = auth()->user()) {
            $roles      = $user->getRoleNames();
            $permission = $user->getAllPermissions();

            return $this->successResponse($user);
        }

        return $this->failedResponse();
    }

    public function changePassword(PasswordChangeRequest $request): JsonResponse
    {
        $user = auth()->user();
        if ($user && Hash::check($request->old_password, $user->password)) {
            User::find($user->id)->update([
                'password'         => Hash::make($request->password),
                'password_changed' => true,
            ]);

            return $this->successResponse([
                'message' => 'Password has been changed',
            ]);
        }

        return $this->failedResponse();
    }

    public function updateProfile(ProfileUpdateRequest $request): JsonResponse
    {
        $user = auth()->user();

        if (isset($request->email)) {
            $check = User::where('email', $request->email)
                ->where('id', '!=', $user->id)
                ->first();

            if ($check) {
                return $this->failedResponse('The email address is already used!');
            }
        }

        $user->update(
            $request->only([
                'name',
                'email',
            ])
        );

        return $this->successResponse([
            'message' => 'Profile updated successfully!',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Sanctum: delete the token used for this request only.
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse([
            'message' => 'Logged out successfully!',
        ]);
    }
}