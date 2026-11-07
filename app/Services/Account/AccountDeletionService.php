<?php

namespace App\Services\Account;

use App\Models\AccountDeletionRequest;
use App\Models\Company;
use App\Models\DeviceToken;
use App\Models\User;
use App\Models\WebPushSubscription;
use App\Services\CompanyBranding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Hapus akun sesuai aturan Play Store / App Store & UU PDP.
 *
 * - Akun biasa: langsung dianonimkan (nama, email, no. HP, NIK, foto) dan tidak bisa login lagi.
 *   Baris user tetap ada agar riwayat aset / maintenance perusahaan tidak rusak.
 * - Administrator TERAKHIR di perusahaan: tidak bisa langsung dihapus (perusahaan akan
 *   kehilangan pengelola), jadi dibuat permintaan penutupan perusahaan untuk diproses tim Vasetra.
 */
class AccountDeletionService
{
    public const DONE = 'deleted';
    public const COMPANY_PENDING = 'company_pending';

    /**
     * @return array{status:string, message:string, request:AccountDeletionRequest}
     */
    public function deleteSelf(User $user, string $source, ?string $reason = null, ?string $ip = null): array
    {
        $user->loadMissing('role', 'company');

        if ($this->isLastAdministrator($user)) {
            $request = AccountDeletionRequest::query()->firstOrCreate(
                ['company_id' => $user->company_id, 'scope' => 'company', 'status' => 'pending'],
                ['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'source' => $source, 'reason' => $reason, 'ip' => $ip]
            );

            return [
                'status' => self::COMPANY_PENDING,
                'message' => 'Anda adalah administrator terakhir, jadi permintaan diproses sebagai penutupan akun perusahaan. Tim Vasetra akan menghubungi Anda dan menyelesaikannya paling lambat 30 hari.',
                'request' => $request,
            ];
        }

        $request = AccountDeletionRequest::query()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'scope' => 'user',
            'source' => $source,
            'reason' => $reason,
            'status' => 'done',
            'processed_at' => now(),
            'note' => 'Dihapus langsung oleh pemilik akun.',
            'ip' => $ip,
        ]);

        $this->anonymize($user);

        return [
            'status' => self::DONE,
            'message' => 'Akun Anda sudah dihapus. Data pribadi Anda telah dihapus dari Vasetra.',
            'request' => $request,
        ];
    }

    /**
     * Permintaan dari formulir publik (tanpa login). Tidak membocorkan apakah email terdaftar.
     */
    public function requestFromForm(string $email, ?string $reason, ?string $ip): AccountDeletionRequest
    {
        $user = User::query()->where('email', strtolower(trim($email)))->first();

        return AccountDeletionRequest::query()->create([
            'company_id' => $user?->company_id,
            'user_id' => $user?->id,
            'name' => $user?->name,
            'email' => strtolower(trim($email)),
            'scope' => $user && $this->isLastAdministrator($user) ? 'company' : 'user',
            'source' => 'form',
            'reason' => $reason,
            'ip' => $ip,
        ]);
    }

    public function isLastAdministrator(User $user): bool
    {
        if ($user->role?->role_code !== 'administrator') {
            return false;
        }

        return !User::query()
            ->where('company_id', $user->company_id)
            ->whereKeyNot($user->id)
            ->where('status', 1)
            ->whereHas('role', fn ($q) => $q->where('role_code', 'administrator'))
            ->exists();
    }

    /**
     * Hapus data pribadi user; akun tidak bisa dipakai lagi.
     */
    public function anonymize(User $user): void
    {
        DB::transaction(function () use ($user) {
            if ($user->photo && !str_starts_with((string) $user->photo, 'http')) {
                Storage::disk('public')->delete(ltrim((string) $user->photo, '/'));
            }

            $user->forceFill([
                'name' => 'Pengguna dihapus',
                'nik' => '-',
                'phone' => 'DEL' . $user->id,
                'email' => 'deleted-' . $user->id . '-' . Str::lower(Str::random(6)) . '@deleted.vasetra.invalid',
                'photo' => null,
                'password' => Hash::make(Str::random(48)),
                'status' => 0,
                'must_change_password' => false,
                'remember_token' => null,
                'last_login_ip' => null,
                'last_login_device' => null,
            ])->save();

            $user->tokens()->delete();
            DeviceToken::query()->where('user_id', $user->id)->delete();
            WebPushSubscription::query()->where('user_id', $user->id)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
        });
    }

    /**
     * Tutup perusahaan: tangguhkan & anonimkan semua user-nya, hapus logo.
     */
    public function closeCompany(Company $company): void
    {
        $company->forceFill(['status' => 0])->save();
        CompanyBranding::remove($company);

        User::query()->where('company_id', $company->id)->get()->each(fn (User $u) => $this->anonymize($u));
    }

    /**
     * Proses permintaan dari backoffice.
     */
    public function process(AccountDeletionRequest $request, ?int $adminId, ?string $note = null): void
    {
        if ($request->scope === 'company' && $request->company) {
            $this->closeCompany($request->company);
        } elseif ($request->user_id && ($user = User::query()->find($request->user_id))) {
            if ($this->isLastAdministrator($user)) {
                // Administrator terakhir: tutup perusahaan sekaligus.
                $this->closeCompany($user->company);
                $request->scope = 'company';
            } else {
                $this->anonymize($user);
            }
        }

        $request->forceFill([
            'status' => 'done',
            'processed_by' => $adminId,
            'processed_at' => now(),
            'note' => $note,
        ])->save();
    }
}
