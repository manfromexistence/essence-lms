<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs out any authenticated account that has been deactivated.
 *
 * `is_active` was previously enforced only at the moment of login. The office's
 * "reject admission" action sets the flag, and the UI presents that as removing
 * the student's access, but nothing checked it again on later requests. A
 * rejected student's session therefore kept working for the rest of its
 * lifetime, and a "Remember me" cookie stayed valid for years because nothing
 * ever rotated remember_token.
 *
 * Deactivating an account also rotates remember_token, so a stolen or parked
 * cookie cannot be replayed after the fact.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Strict `=== false` rather than a falsy test. Eloquent's create()
        // returns an instance populated only with the attributes that were
        // actually set plus the id, so a User built without an explicit
        // is_active reads back as NULL — even though the column defaults to
        // true in the database. A falsy check would treat that missing
        // attribute as "deactivated" and lock out users who are perfectly
        // active. A genuinely deactivated account is persisted as boolean
        // false, which this still catches.
        if ($user !== null && $user->is_active === false) {
            // Invalidate the current session and cycle the remember-me token so
            // the recaller cookie stops working.
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Your account has been deactivated.'], 403);
            }

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Your account has been deactivated. Please contact the office.']);
        }

        return $next($request);
    }
}
