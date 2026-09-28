<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\ValidationMessages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        // Already signed in? Go straight to the correct dashboard.
        if (Auth::check()) {
            return redirect()->route($this->dashboardRoute(Auth::user()));
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        // Trimmed here so "  admin  " is not rejected as a wrong username,
        // and so a whitespace-only value is reported as "enter your username"
        // rather than as a credential failure.
        $request->merge([
            'username' => is_string($request->input('username'))
                ? trim($request->input('username'))
                : $request->input('username'),
        ]);

        $credentials = $request->validate(
            [
                'username' => ['required', 'string'],
                'password' => ['required', 'string'],
            ],
            ValidationMessages::credentials(),
        );

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            // One message for both fields, and attached to the username so the
            // form can mark it. Saying which half was wrong would confirm
            // which usernames exist to anyone who tried one.
            //
            // The password is deliberately not flashed back: the field is
            // emptied so a failed attempt does not leave it on screen.
            throw ValidationException::withMessages([
                'username' => ValidationMessages::invalidCredentials(),
            ]);
        }

        // Prevent session fixation.
        $request->session()->regenerate();

        $user = Auth::user();

        return redirect()
            ->intended(route($this->dashboardRoute($user)))
            ->with('success', "Welcome back, {$user->username}!");
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', ValidationMessages::loggedOut());
    }

    private function dashboardRoute(object $user): string
    {
        return $user->isAdmin() ? 'admin.dashboard' : 'staff.dashboard';
    }
}
