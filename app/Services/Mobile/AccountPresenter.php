<?php

namespace App\Services\Mobile;

use App\Models\Permission;
use App\Models\User;
use App\Services\Subscription\SubscriptionService;
use Carbon\Carbon;

/**
 * Bentuk data akun untuk aplikasi mobile (login, /me, /profile).
 * Satu tempat agar Flutter selalu menerima struktur yang sama.
 */
class AccountPresenter
{
    public function __construct(
        protected SubscriptionService $subscriptions
    ) {
    }

    public function present(User $user): array
    {
        $user->loadMissing(['role', 'company']);

        return [
            'user' => $this->user($user),
            'company' => $this->company($user),
            'permissions' => $this->permissions($user),
            'subscription' => $this->subscription($user),
        ];
    }

    public function user(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'nik' => $user->nik,
            'photo_url' => $user->photo ? asset('storage/' . ltrim($user->photo, '/')) : null,
            'status' => (int) $user->status,
            'role' => $user->role?->role_name,
            'role_code' => $user->role?->role_code,
            'is_administrator' => $user->isAdministrator(),
            'must_change_password' => (bool) $user->must_change_password,
            'email_verified' => $user->hasVerifiedEmail(),
            'last_login_at' => $user->last_login_at
                ? Carbon::parse($user->last_login_at)->toIso8601String()
                : null,
        ];
    }

    public function company(User $user): array
    {
        return [
            'id' => $user->company?->id,
            'name' => $user->company?->company_name,
            'code' => $user->company?->company_code,
            'logo_url' => $user->company?->logo
                ? asset('storage/' . ltrim($user->company->logo, '/'))
                : null,
        ];
    }

    /**
     * Daftar kode permission user. Administrator = seluruh permission.
     * Flutter memakai ini untuk menampilkan/menyembunyikan menu & tombol.
     */
    public function permissions(User $user): array
    {
        if ($user->isAdministrator()) {
            return Permission::query()->orderBy('permission_code')->pluck('permission_code')->all();
        }

        if (!$user->role || (int) $user->role->status !== 1) {
            return [];
        }

        return $user->role->permissions()
            ->orderBy('permission_code')
            ->pluck('permission_code')
            ->all();
    }

    /**
     * Ringkasan masa aktif langganan (tanpa harga / data pembayaran).
     */
    public function subscription(User $user): array
    {
        $companyId = (int) $user->company_id;
        $current = $this->subscriptions->current($companyId);
        $queued = $this->subscriptions->queued($companyId);

        // Masa aktif bersambung bila ada paket di antrean.
        $base = $current?->isUpgrade() ? $current->parent : $current;
        $activeUntil = $queued?->end_date ?? $base?->end_date ?? $current?->end_date;
        $activeUntil = $activeUntil ? Carbon::parse($activeUntil)->startOfDay() : null;

        return [
            'is_active' => $current !== null,
            'plan' => $current?->plan?->plan_name,
            'plan_code' => $current?->plan?->plan_code,
            'is_trial' => $this->subscriptions->isTrial($current),
            'is_upgrade' => (bool) $current?->isUpgrade(),
            'active_until' => $activeUntil?->toDateString(),
            'days_left' => $activeUntil
                ? (int) today()->diffInDays($activeUntil, false)
                : null,
            'queued_plan' => $queued?->plan?->plan_name,
        ];
    }
}
