<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    /**
     * Show the Admin Login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            return redirect()->route('dashboard.index');
        }

        return view('auth.login');
    }

    /**
     * Handle admin session authentication.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $loginInput = trim($credentials['login']);
        $password = $credentials['password'];
        $remember = $request->boolean('remember');

        // Check if login input is an email address
        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $attemptCredentials = [
            $fieldType => $loginInput,
            'password' => $password,
        ];

        if (! Auth::attempt($attemptCredentials, $remember)) {
            // Fallback attempt: if user entered username as email or vice-versa
            $altField = ($fieldType === 'email') ? 'username' : 'email';
            $fallbackAttempt = [
                $altField => $loginInput,
                'password' => $password,
            ];

            if (! Auth::attempt($fallbackAttempt, $remember)) {
                throw ValidationException::withMessages([
                    'login' => ['The provided credentials do not match our records.'],
                ]);
            }
        }

        /** @var User $user */
        $user = Auth::user();

        // Enforce admin role requirement
        if ($user->role !== 'admin') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login' => 'Unauthorized. Only system administrators can access this portal.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard.index'))
            ->with('success', "Welcome back, {$user->name}!");
    }

    /**
     * Log out of the admin session.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }
}
