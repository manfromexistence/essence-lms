<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $credentials['is_active'] = true;

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return Auth::user()->must_change_password
                ? redirect()->route('password.change')
                : redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        // Only send a reset link to an ACTIVE user. Pending/rejected students
        // cannot log in anyway, and sending a link to them would (a) leak that
        // an account exists and (b) produce a link they cannot use.
        $user = User::where('email', $request->email)->where('is_active', true)->first();
        if ($user) {
            Password::sendResetLink($request->only('email'));
        }

        // Always show the same message regardless of whether the account exists,
        // so we don't disclose which emails have accounts.
        return back()->with('success', 'If an active account exists for that email, a secure password link has been sent.');
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        // Pre-check: only active accounts may reset their password. Doing this
        // here (before Password::reset) means a pending/rejected student never
        // consumes a token or triggers a 403 mid-request.
        $user = User::where('email', $validated['email'])->first();
        if ($user && ! $user->is_active) {
            return back()->withErrors(['email' => __('passwords.user')])->withInput($request->only('email'));
        }

        $status = Password::reset(
            $validated,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'must_change_password' => false,
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', __($status))
            : back()->withErrors(['email' => __($status)])->withInput($request->only('email'));
    }

    public function showChangePassword()
    {
        return view('auth.change-password');
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Your password was changed securely.');
    }
}
