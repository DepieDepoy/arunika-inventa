<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use App\Http\Controllers\Controller;
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

        /*
        |--------------------------------------------------------------------------
        | FIND USER
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | CHECK USER STATUS
        |--------------------------------------------------------------------------
        */

        if ($user->status != 1) {
            return back()
                ->withErrors([
                    'login' => 'Your account is inactive.'
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK PASSWORD
        |--------------------------------------------------------------------------
        */

        if (!Hash::check($request->password, $user->password)) {
            return back()
                ->withErrors([
                    'login' => 'Invalid email/WhatsApp number or password.'
                ])
                ->onlyInput('login');
        }

        /*
        |--------------------------------------------------------------------------
        | LOGIN
        |--------------------------------------------------------------------------
        */

        Auth::login(
            $user,
            $request->boolean('remember')
        );

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $request->session()->regenerate();

        /*
        |--------------------------------------------------------------------------
        | CHECK SUBSCRIPTION AFTER LOGIN
        |--------------------------------------------------------------------------
        */

        $subscription = $user->company?->activeSubscription;

        /*
        |--------------------------------------------------------------------------
        | SUBSCRIPTION AKTIF
        |--------------------------------------------------------------------------
        |
        | Semua user boleh masuk aplikasi.
        |
        */

        if ($subscription) {
            return redirect()->route('dashboard.home');
        }

        /*
        |--------------------------------------------------------------------------
        | SUBSCRIPTION EXPIRED
        |--------------------------------------------------------------------------
        */

        $isAdministrator =
            $user->role &&
            $user->role->role_code === 'administrator';

        /*
        |--------------------------------------------------------------------------
        | ADMINISTRATOR
        |--------------------------------------------------------------------------
        |
        | Administrator tetap boleh masuk aplikasi untuk:
        |
        | - melihat data
        | | - membuka halaman subscription
        | - melakukan perpanjangan
        |
        | Tetapi operasi Add/Edit/Delete akan tetap diblokir
        | oleh middleware subscription.active.
        |
        */

        if ($isAdministrator) {
            return redirect()->route('dashboard.home');
        }

        /*
        |--------------------------------------------------------------------------
        | NON-ADMINISTRATOR
        |--------------------------------------------------------------------------
        |
        | Subscription expired:
        | user tidak boleh masuk dashboard.
        |
        */

        return redirect()
            ->route('subscription.expired')
            ->with(
                'warning',
                'Subscription perusahaan Anda telah berakhir. Silakan hubungi Administrator untuk melakukan perpanjangan.'
            );
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