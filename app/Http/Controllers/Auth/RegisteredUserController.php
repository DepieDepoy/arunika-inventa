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
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register.index');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'digits_between:10,15', 'unique:users,phone'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
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

        // =====================================================
        // GENERATE COMPANY CODE
        // =====================================================

        $companyCode = CompanyHelper::generateCode(
            $request->company_name
        );

        // =====================================================
        // CEK APAKAH PERUSAHAAN SUDAH TERDAFTAR
        // =====================================================

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
            | AMBIL PLAN FREE
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

            /*
            |--------------------------------------------------------------------------
            | TANGGAL SUBSCRIPTION FREE
            |--------------------------------------------------------------------------
            |
            | trial_days diambil dari tabel plans.
            | Jadi kalau nanti FREE diubah dari 14 hari menjadi 7/30 hari,
            | controller tidak perlu diubah.
            |
            */

            $startDate = today();

            $endDate = $startDate->copy()
                ->addDays($freePlan->trial_days);

            /*
            |--------------------------------------------------------------------------
            | SIMPAN PERUSAHAAN
            |--------------------------------------------------------------------------
            */

            $company = Company::create([
                'company_name' => trim($request->company_name),
                'company_code' => $companyCode,
                'status'       => 1,

                // Snapshot subscription aktif
                'subscription_plan'   => $freePlan->plan_code,
                'subscription_status' => 'active',
                'started_at'          => $startDate,
                'expired_at'          => $endDate,
            ]);

            /*
            |--------------------------------------------------------------------------
            | BUAT SUBSCRIPTION FREE TRIAL
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
            | GENERATE DEFAULT ROLE
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
            | AMBIL ROLE ADMINISTRATOR
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
            | SIMPAN USER ADMINISTRATOR
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
            ]);

            /*
            |--------------------------------------------------------------------------
            | ADMINISTRATOR MENDAPATKAN SEMUA PERMISSION
            |--------------------------------------------------------------------------
            */

            $adminRole->permissions()->sync(
                Permission::pluck('id')
            );

            /*
            |--------------------------------------------------------------------------
            | COMMIT TRANSACTION
            |--------------------------------------------------------------------------
            */

            DB::commit();

            event(new Registered($user));

            return redirect()
                ->route('login')
                ->with(
                    'success',
                    'Registration successful. Please login.'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withErrors([
                    'company_name' => $e->getMessage()
                ])
                ->withInput();
        }
    }
}