<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Helpers\CompanyHelper;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Str;

use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'digits_between:10,15', 'unique:users,phone'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:users,email'
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
        ], [
            'name.required' => 'Full name is required.',
            'nik.required' => 'ID Person is required.',
            'company_name.required' => 'Company name is required.',
            'phone.required' => 'WhatsApp number is required.',
            'phone.unique' => 'This WhatsApp number is already in use.',
            'phone.digits_between' => 'WhatsApp number must be between 10 and 15 digits.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already in use.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password_confirmation.required' => 'Please confirm your password.',
        ]);

        $companyCode = CompanyHelper::generateCode($request->company_name);

        if (Company::where('company_code', $companyCode)->exists()) {
            return back()
                ->withErrors([
                    'company_name' => 'Perusahaan sudah terdaftar.'
                ])
                ->withInput();
        }

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | FREE PLAN
            |--------------------------------------------------------------------------
            */

            $freePlan = Plan::where('plan_code', 'FREE')
                ->where('status', 1)
                ->first();

            if (!$freePlan) {
                throw new \Exception(
                    'Plan FREE belum tersedia. Silakan hubungi administrator.'
                );
            }

            $startDate = today();

            $endDate = $startDate
                ->copy()
                ->addDays($freePlan->trial_days);

            /*
            |--------------------------------------------------------------------------
            | COMPANY
            |--------------------------------------------------------------------------
            */

            $company = Company::create([
                'company_name' => trim($request->company_name),
                'company_code' => $companyCode,
                'status' => 1,
                'subscription_plan' => $freePlan->plan_code,
                'subscription_status' => 'active',
                'started_at' => $startDate,
                'expired_at' => $endDate,
            ]);

            /*
            |--------------------------------------------------------------------------
            | SUBSCRIPTION
            |--------------------------------------------------------------------------
            */

            Subscription::create([
                'company_id' => $company->id,
                'plan_id' => $freePlan->id,
                'billing_cycle' => 'trial',
                'price' => 0,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active',
                'payment_status' => 'paid',
            ]);

            /*
            |--------------------------------------------------------------------------
            | DEFAULT ROLES
            |--------------------------------------------------------------------------
            */

            $defaultRoles = [
                'Administrator',
                'Manager',
                'Supervisor',
                'Staff',
            ];

            foreach ($defaultRoles as $role) {
                Role::create([
                    'company_id' => $company->id,
                    'role_name' => $role,
                    'role_code' => Str::slug($role, '_'),
                    'status' => 1,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | ADMIN ROLE
            |--------------------------------------------------------------------------
            */

            $adminRole = Role::where('company_id', $company->id)
                ->where('role_code', 'administrator')
                ->first();

            if (!$adminRole) {
                throw new \Exception(
                    'Role Administrator gagal dibuat.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | USER
            |--------------------------------------------------------------------------
            */

            $user = User::create([
                'company_id' => $company->id,
                'role_id' => $adminRole->id,
                'name' => $request->name,
                'nik' => $request->nik,
                'phone' => $request->phone,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'status' => 1,
                'email_verified_at' => null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | ADMIN PERMISSIONS
            |--------------------------------------------------------------------------
            */

            $adminRole->permissions()->sync(
                Permission::pluck('id')
            );

            /*
            |--------------------------------------------------------------------------
            | COMMIT DATABASE
            |--------------------------------------------------------------------------
            */

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withErrors([
                    'company_name' => $e->getMessage()
                ])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | SEND VERIFICATION EMAIL
        |--------------------------------------------------------------------------
        |
        | Dilakukan setelah database berhasil commit.
        |
        */

        try {

            event(new Registered($user));

        } catch (\Throwable $e) {

            report($e);

            /*
            |--------------------------------------------------------------------------
            | User tetap dibuat.
            | User masih bisa melakukan resend verification.
            |--------------------------------------------------------------------------
            */

            return redirect()
                ->route('login')
                ->with(
                    'warning',
                    'Registrasi berhasil, tetapi email verifikasi gagal dikirim. Silakan gunakan fitur kirim ulang verifikasi.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | SIMPAN DATA UNTUK RESEND VERIFICATION
        |--------------------------------------------------------------------------
        */

        session([
            'verification_user_id' => $user->id,
            'verification_email' => $user->email,
        ]);

        /*
        |--------------------------------------------------------------------------
        | REDIRECT LOGIN
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('login')
            ->with(
                'success',
                'Registrasi berhasil! Silakan cek email Anda untuk melakukan verifikasi sebelum login.'
            );
    }
}