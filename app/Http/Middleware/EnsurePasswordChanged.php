<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Login page -> correct password -> (password_changed false) -> create-new-password page -> dashboard
 *
 * Re-uses the existing SMS reset "create new password" page, but skips the
 * phone/OTP steps because the user has just proven who they are by logging in.
 */
class EnsurePasswordChanged
{
    /**
     * Routes that stay reachable while the password is unchanged.
     * 'password.sms.*' = your existing create-new-password page + its POST.
     */
    protected array $except = [
        'password.sms.*',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Guests and users who already changed their password pass through
        if (! $user || $user->password_changed) {
            return $next($request);
        }

        if ($request->routeIs($this->except)) {
            // Make sure the session is primed even if they land directly on the form
            $this->prime($request, $user->id);
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'You must create a new password before continuing.',
            ], 423);
        }

        $this->prime($request, $user->id);

        return redirect()
            ->route('password.sms.reset.form')
            ->with('status', 'For your security, please create a new password to continue.');
    }

    /**
     * Put the same session flags the OTP step would have set.
     */
    protected function prime(Request $request, int $userId): void
    {
        $request->session()->put([
            'sms_reset:user_id'     => $userId,
            'sms_reset:verified'    => true,
            'sms_reset:verified_at' => now()->timestamp,
            'sms_reset:forced'      => true,
        ]);
    }
}
