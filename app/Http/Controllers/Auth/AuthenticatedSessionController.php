<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login.index');
    }

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
        | DETERMINE LOGIN FIELD
        |--------------------------------------------------------------------------
        */

        $field = filter_var($request->login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'phone';

        /*
        |--------------------------------------------------------------------------
        | FIND USER
        |--------------------------------------------------------------------------
        */

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
        | ACCOUNT STATUS
        |--------------------------------------------------------------------------
        */

        if ($user->status != 1) {
            return back()
                ->withErrors([
                    'login' => 'Your account is inactive.'
                ])
                ->onlyInput('login');
        }

        /*
        |--------------------------------------------------------------------------
        | PASSWORD
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
        | EMAIL VERIFICATION
        |--------------------------------------------------------------------------
        |
        | Password benar tetapi email belum diverifikasi.
        | JANGAN login ke dashboard.
        |
        */

        if (!$user->hasVerifiedEmail()) {

            session([
                'verification_user_id' => $user->id,
                'verification_email' => $user->email,
            ]);

            return back()
                ->with(
                    'error',
                    'Email Anda belum diverifikasi. Silakan cek inbox email Anda terlebih dahulu.'
                )
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

        /*
        |--------------------------------------------------------------------------
        | UPDATE LAST LOGIN
        |--------------------------------------------------------------------------
        */

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | REGENERATE SESSION
        |--------------------------------------------------------------------------
        */

        $request->session()->regenerate();

        /*
        |--------------------------------------------------------------------------
        | SUBSCRIPTION
        |--------------------------------------------------------------------------
        */

        $subscription = $user->company?->activeSubscription;

        if ($subscription) {
            return redirect()->route('dashboard.home');
        }

        /*
        |--------------------------------------------------------------------------
        | ADMINISTRATOR BYPASS
        |--------------------------------------------------------------------------
        */

        $isAdministrator =
            $user->role &&
            $user->role->role_code === 'administrator';

        if ($isAdministrator) {
            return redirect()->route('dashboard.home');
        }

        /*
        |--------------------------------------------------------------------------
        | SUBSCRIPTION EXPIRED
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('subscription.expired')
            ->with(
                'warning',
                'Subscription perusahaan Anda telah berakhir. Silakan hubungi Administrator untuk melakukan perpanjangan.'
            );
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}