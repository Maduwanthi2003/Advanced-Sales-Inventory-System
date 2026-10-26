<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(
        Request $request,
        Closure $next,
        ?string $permission = null
    ): Response {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Prevent deactivated users or users with deactivated roles from using existing sessions
        if (!$user->is_active || !$user->role || !$user->role->is_active) {
            abort(403, 'Your account or assigned role is inactive.');
        }

        // Enforce single active session per user account (fail-closed)
        $sessionToken = (string) $request->session()->get('auth_session_token', '');
        $activeHash = (string) (User::whereKey($user->id)->value('active_session_hash') ?? '');
        $computedHash = $sessionToken !== '' ? hash('sha256', $sessionToken) : '';

        if (
            $sessionToken === ''
            || $activeHash === ''
            || !hash_equals($activeHash, $computedHash)
        ) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your account was signed in from another device. Please log in again.',
                ], 401);
            }

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Your account was signed in from another device. Please log in again.'
                );
        }

        if ($permission !== null && $permission !== '' && !$user->hasPermission($permission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthorized action.'
                ], 403);
            }

            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
