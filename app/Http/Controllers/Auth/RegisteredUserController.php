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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
//use Illuminate\Validation\Rules;
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
        ],
        [
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

        // Generate company code
        $companyCode = CompanyHelper::generateCode($request->company_name);

        // Cek apakah perusahaan sudah ada
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
            | Ambil Plan FREE
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
            | Simpan perusahaan
            |--------------------------------------------------------------------------
            */

            $company = Company::create([
                'company_name' => trim($request->company_name),
                'company_code' => $companyCode,
                'status'       => 1,

                // Tetap isi data lama untuk sementara
                'subscription_plan'   => $freePlan->plan_code,
                'subscription_status' => 'active',
                'started_at'          => now(),
                'expired_at'          => now()->addDays($freePlan->duration_days),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Buat Subscription FREE Trial
            |--------------------------------------------------------------------------
            */

            Subscription::create([
                'company_id' => $company->id,
                'plan_id'    => $freePlan->id,
                'start_date' => today(),
                'end_date'   => today()->addDays($freePlan->duration_days),
                'status'     => 'active',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Generate default role
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
                    'role_name'  => $role,
                    'role_code'  => Str::slug($role, '_'),
                    'status'     => 1,
                ]);

            }

            /*
            |--------------------------------------------------------------------------
            | Ambil role Administrator
            |--------------------------------------------------------------------------
            */

            $adminRole = Role::where('company_id', $company->id)
                ->where('role_code', 'administrator')
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Simpan user Administrator
            |--------------------------------------------------------------------------
            */

            $user = User::create([
                'company_id' => $company->id,
                'role_id'    => $adminRole->id,

                'name'       => $request->name,
                'nik'        => $request->nik,
                'phone'      => $request->phone,
                'email'      => $request->email,
                'password'   => Hash::make($request->password),
                'status'     => 1,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Administrator mendapatkan semua permission
            |--------------------------------------------------------------------------
            */

            $adminRole->permissions()->sync(
                Permission::pluck('id')
            );

            DB::commit();

            event(new Registered($user));

            return redirect()
                ->route('login')
                ->with('success', 'Registration successful. Please login.');

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