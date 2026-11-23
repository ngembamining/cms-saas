<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | Registers new users. Re-implemented without the legacy RegistersUsers
    | trait, which was removed in Laravel 11.
    |
    | Field names are aligned with App\Models\User:
    |   - full_name (not "name")
    |   - username
    |   - email
    |   - password
    |   - role (defaults to "staff")
    |
    */

    /**
     * Where to redirect users after registration.
     */
    protected string $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Show the application registration form.
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request for the application.
     */
    public function register(Request $request)
    {
        // ----------------------------------------------------------------
        // 1. Validate the incoming request
        // ----------------------------------------------------------------
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'username'  => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'string', 'confirmed', Password::min(8)],
        ], [
            // Optional custom messages
            'username.alpha_dash' => __('Username may only contain letters, numbers, dashes and underscores.'),
            'username.unique'     => __('That username is already taken.'),
            'email.unique'        => __('That email address is already registered.'),
        ]);

        // ----------------------------------------------------------------
        // 2. Create the user
        // ----------------------------------------------------------------
        $user = User::create([
            'full_name' => $data['full_name'],
            'username'  => $data['username'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'role'      => 'staff', // default role — change as needed
        ]);

        // ----------------------------------------------------------------
        // 3. Log the user in
        // ----------------------------------------------------------------
        Auth::login($user);

        // Regenerate session to prevent fixation
        $request->session()->regenerate();

        // ----------------------------------------------------------------
        // 4. Redirect to home / dashboard
        // ----------------------------------------------------------------
        return redirect($this->redirectTo)
            ->with('status', __('Account created successfully. Welcome!'));
    }
}