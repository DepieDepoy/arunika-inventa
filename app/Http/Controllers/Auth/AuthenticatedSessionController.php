<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login.index');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(
            [
                'login' => ['required'],
                'password' => ['required'],
            ],
            [
                'login.required' => 'Email address or WhatsApp number is required.',
                'password.required' => 'Password is required.',
            ]
        );

        $field = filter_var($request->login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'phone';

        $user = User::where($field, $request->login)->first();

        if (!$user) {

            return back()
                ->withErrors([
                    'login' => 'Invalid email/WhatsApp number or password.'
                ])
                ->onlyInput('login');
        }

        if ($user->status != 1) {

            return back()
                ->withErrors([
                    'login' => 'Your account is inactive.'
                ]);
        }

        if (!Hash::check($request->password, $user->password)) {

            return back()
                ->withErrors([
                    'login' => 'Invalid email/WhatsApp number or password.'
                ])
                ->onlyInput('login');
        }

        Auth::login($user, $request->boolean('remember'));
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);
        $request->session()->regenerate();

        return redirect()->intended(route('cms.home'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
