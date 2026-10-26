<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    private const MAX_ACCOUNT_ATTEMPTS = 3;
    private const MAX_IP_ATTEMPTS = 10;
    private const LOCKOUT_SECONDS = 300;

    // Display the login form.
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    // Validate credentials and establish an authenticated session.
    public function login(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email', ''))),
        ]);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $accountKey = $this->accountThrottleKey($request);
        $ipKey = $this->ipThrottleKey($request);

        if ($this->isLockedOut($accountKey, $ipKey)) {
            return $this->lockoutResponse($accountKey, $ipKey);
        }

        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($accountKey, self::LOCKOUT_SECONDS);
            RateLimiter::hit($ipKey, self::LOCKOUT_SECONDS);

            if ($this->isLockedOut($accountKey, $ipKey)) {
                return $this->lockoutResponse($accountKey, $ipKey);
            }

            return back()
                ->withErrors([
                    'email' => 'Invalid email or password.',
                ])
                ->onlyInput('email');
        }

        $user = $request->user();
        $user?->loadMissing('role');

        if (
            !$user
            || !(bool) $user->is_active
            || !$user->role
            || !(bool) $user->role->is_active
        ) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'email' => 'Your account or assigned role is inactive. Please contact an administrator.',
                ])
                ->onlyInput('email');
        }

        RateLimiter::clear($accountKey);
        RateLimiter::clear($ipKey);

        $rawSessionToken = Str::random(64);
        $sessionTokenHash = hash('sha256', $rawSessionToken);

        $lockedUser = DB::transaction(function () use ($user, $sessionTokenHash) {
            $locked = User::whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $locked->forceFill([
                'active_session_hash' => $sessionTokenHash,
                'remember_token' => Str::random(60),
                'last_login_at' => now(),
            ])->save();

            return $locked;
        }, 3);

        if ($remember) {
            Auth::login($lockedUser, true);
        } else {
            Auth::setUser($lockedUser);
        }

        $request->session()->regenerate();
        $request->session()->put('auth_session_token', $rawSessionToken);

        return redirect()->intended(route('dashboard'));
    }

    // End the current session and return to the login page.
    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        $sessionToken = (string) $request->session()->get('auth_session_token', '');

        if ($user) {
            $isCurrentActiveSession = DB::transaction(function () use ($user, $sessionToken) {
                $locked = User::whereKey($user->id)
                    ->lockForUpdate()
                    ->first();

                if (
                    $locked
                    && $sessionToken !== ''
                    && !empty($locked->active_session_hash)
                    && hash_equals((string) $locked->active_session_hash, hash('sha256', $sessionToken))
                ) {
                    $locked->forceFill([
                        'active_session_hash' => null,
                        'remember_token' => Str::random(60),
                    ])->save();

                    return true;
                }

                return false;
            }, 3);

            if ($isCurrentActiveSession) {
                Auth::logout();
            } else {
                Auth::forgetGuards();
            }
        } else {
            Auth::logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // Build the rate-limit key for a login account.
    private function accountThrottleKey(Request $request): string
    {
        $email = Str::transliterate(Str::lower(trim((string) $request->input('email', ''))));

        return 'login:account:' . $email . '|' . (string) $request->ip();
    }

    // Build the rate-limit key for the request IP address.
    private function ipThrottleKey(Request $request): string
    {
        return 'login:ip:' . (string) $request->ip();
    }

    // Check whether the account or IP has reached its login limit.
    private function isLockedOut(string $accountKey, string $ipKey): bool
    {
        return RateLimiter::tooManyAttempts($accountKey, self::MAX_ACCOUNT_ATTEMPTS)
            || RateLimiter::tooManyAttempts($ipKey, self::MAX_IP_ATTEMPTS);
    }

    // Record throttling attempts and return the lockout response.
    private function lockoutResponse(string $accountKey, string $ipKey): RedirectResponse
    {
        $seconds = max(
            RateLimiter::availableIn($accountKey),
            RateLimiter::availableIn($ipKey),
            1
        );

        $minutes = (int) ceil($seconds / 60);
        $minuteLabel = Str::plural('minute', $minutes);

        return back()
            ->withErrors([
                'email' => "Too many failed login attempts. Please try again in {$minutes} {$minuteLabel} ({$seconds} seconds).",
            ])
            ->onlyInput('email');
    }
}
