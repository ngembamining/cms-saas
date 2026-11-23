<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('branch');

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'branch_name' => $user->branch->name ?? null,
                'password_changed' => (bool) $user->password_changed,
                'two_factor_enabled' => (bool) $user->two_factor_enabled,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'full_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:20',
        ]);

        $user->update($request->only(['full_name', 'email', 'phone_number']));

        return response()->json(['status' => true, 'message' => 'Profile updated.']);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->password_changed = true;
        $user->save();

        return response()->json(['status' => true, 'message' => 'Password changed.']);
    }

    public function updateAvatar(Request $request)
    {
        return response()->json(['status' => false, 'message' => 'Avatar upload not implemented.'], 501);
    }

    public function twoFactorEnable(Request $request)
    {
        return response()->json(['status' => false, 'message' => 'Not implemented.'], 501);
    }

    public function twoFactorDisable(Request $request)
    {
        return response()->json(['status' => false, 'message' => 'Not implemented.'], 501);
    }
}