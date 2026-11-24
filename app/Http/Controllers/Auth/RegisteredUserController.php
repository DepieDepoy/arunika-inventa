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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register.index');
    }

    public function store(Request $request): RedirectResponse
    {
        // Anti bot: honeypot, waktu isi form, Cloudflare Turnstile (bila diaktifkan).
        if ($botError = \App\Support\BotGuard::check($request, 'register')) {
            return back()
                ->withErrors(['email' => $botError])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        // Nama & nama perusahaan: huruf Latin + tanda baca umum, tanpa link.
        // (Serangan spam memakai kolom nama untuk menyisipkan teks & link penipuan
        // yang ikut terkirim di email verifikasi ke alamat korban.)
        $noLink = 'not_regex:/(https?:|www\.|:\/\/)/i';

        $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[\p{Latin}\p{M}\s.,\'\-]+$/u', $noLink],
            'nik' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9.\-\/ ]+$/'],
            'company_name' => ['required', 'string', 'max:150', 'regex:/^[\p{Latin}\p{M}0-9\s.,\'&()\-\/]+$/u', $noLink],
            'phone' => [
                'required',
                'digits_between:10,15',
                'unique:users,phone',
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                // Produksi: domain email harus benar-benar punya server email (DNS).
                app()->isProduction() ? 'email:rfc,dns' : 'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'password_confirmation' => ['required'],
        ], [
            'name.required' => 'Full name is required.',
            'name.max' => 'Full name may be at most 100 characters.',
            'name.regex' => 'Full name may only contain letters, spaces, and . , \' -',
            'name.not_regex' => 'Full name may not contain links.',
            'nik.required' => 'ID Person is required.',
            'nik.max' => 'ID Person may be at most 50 characters.',
            'nik.regex' => 'ID Person may only contain letters, numbers, and . - /',
            'company_name.required' => 'Company name is required.',
            'company_name.max' => 'Company name may be at most 150 characters.',
            'company_name.regex' => 'Company name may only contain letters, numbers, spaces, and . , \' & ( ) - /',
            'company_name.not_regex' => 'Company name may not contain links.',
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

        $companyCode = CompanyHelper::generateCode(
            trim($request->company_name)
        );

        // Pastikan kode perusahaan aman digunakan sebagai nama folder.
        if (
            ! is_string($companyCode) ||
            ! preg_match('/\A[a-zA-Z0-9_-]+\z/', $companyCode)
        ) {
            return back()
                ->withErrors([
                    'company_name' => 'Kode perusahaan tidak valid.',
                ])
                ->withInput();
        }

        if (
            Company::where('company_code', $companyCode)->exists()
        ) {
            return back()
                ->withErrors([
                    'company_name' => 'Perusahaan sudah terdaftar.',
                ])
                ->withInput();
        }

        $user = null;
        $company = null;

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

            if (! $freePlan) {
                throw new \RuntimeException(
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
                'company_id'          => $company->id,
                'plan_id'              => $freePlan->id,
                'billing_cycle'        => 'trial',
                'price'                => 0,

                'base_storage_bytes'   => $freePlan->max_storage_bytes,
                'storage_addon_bytes'  => 0,
                'total_storage_bytes'  => $freePlan->max_storage_bytes,

                'storage_addon_gb'     => 0,
                'storage_addon_price'  => 0,
                'discount_amount'      => 0,
                'total_price'          => 0,

                'start_date'           => $startDate,
                'end_date'             => $endDate,
                'status'               => 'active',
                'payment_status'       => 'paid',
            ]);

            /*
            |--------------------------------------------------------------------------
            | DEFAULT ROLES
            |--------------------------------------------------------------------------
            */

            // Administrator, Teknisi, Karyawan + permission bawaannya.
            \App\Support\DefaultRoles::provision($company->id);

            /*
            |--------------------------------------------------------------------------
            | ADMIN ROLE
            |--------------------------------------------------------------------------
            */

            $adminRole = Role::where('company_id', $company->id)
                ->where('role_code', 'administrator')
                ->first();

            if (! $adminRole) {
                throw new \RuntimeException(
                    'Role Administrator gagal dibuat.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | FIRST USER
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

            // Analitik funnel (backoffice > Analitik).
            app(\App\Services\Analytics\AnalyticsTracker::class)
                ->event($request, \App\Models\AnalyticsHit::SIGNUP, (int) $user->id, (int) $company->id);

        } catch (Throwable $e) {
            DB::rollBack();

            report($e);

            return back()
                ->withErrors([
                    'company_name' => app()->isLocal()
                        ? $e->getMessage()
                        : 'Registrasi gagal diproses. Silakan coba kembali.',
                ])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE COMPANY STORAGE FOLDERS
        |--------------------------------------------------------------------------
        |
        | Dibuat setelah transaksi database berhasil.
        | Kegagalan folder tidak membatalkan registrasi yang sudah tersimpan.
        |
        */

        try {
            $companyRoot = storage_path(
                'app/public/documents/' . $company->company_code
            );

            $companyFolders = [
                $companyRoot . '/assets/img',
                $companyRoot . '/assets/inv',
                $companyRoot . '/maintenance/progress',
                $companyRoot . '/maintenance/result',
            ];

            foreach ($companyFolders as $folderPath) {
                File::ensureDirectoryExists(
                    $folderPath,
                    0755,
                    true
                );
            }

            Log::info('Company storage folders initialized.', [
                'company_id' => $company->id,
                'company_code' => $company->company_code,
            ]);

        } catch (Throwable $e) {
            // Registrasi tetap berhasil; error dapat diperiksa di log.
            Log::error('Failed to initialize company storage folders.', [
                'company_id' => $company->id,
                'company_code' => $company->company_code,
                'error' => $e->getMessage(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | SEND VERIFICATION EMAIL
        |--------------------------------------------------------------------------
        */

        try {
            event(new Registered($user));

        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('login')
                ->with(
                    'warning',
                    'Registrasi berhasil, tetapi email verifikasi gagal dikirim. Silakan gunakan fitur kirim ulang verifikasi.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | RESEND VERIFICATION SESSION
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
