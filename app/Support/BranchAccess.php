<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Aturan akses multi-cabang.
 *
 * - Role sama untuk semua cabang; setiap user punya cabang (Kantor Pusat / Surabaya / ...).
 * - Administrator di Kantor Pusat melihat SEMUA cabang (bisa memfilter per cabang).
 * - User lain — termasuk Administrator di cabang — hanya melihat data cabangnya:
 *   aset, user, maintenance, mutasi, audit, laporan.
 * - Teknisi juga melihat aset cabang yang DILAYANINYA: cabang tanpa teknisi
 *   otomatis dilayani teknisi Kantor Pusat, atau sesuai pengaturan
 *   "Maintenance ditangani oleh" (branches.service_branch_id).
 *
 * Pembatasan aset diterapkan otomatis lewat global scope (App\Models\Scopes\BranchScope).
 */
final class BranchAccess
{
    /** Cache per request. */
    private static array $headOffice = [];
    private static array $serviceMap = [];
    private static array $seesAll = [];
    private static array $accessible = [];

    /**
     * Boleh melihat semua cabang?
     */
    public static function seesAll(?User $user = null): bool
    {
        $user ??= Auth::user();

        if (!$user instanceof User) {
            return true; // proses sistem (queue, console)
        }

        return self::$seesAll[$user->id . ':' . $user->role_id . ':' . $user->branch_id] ??= $user->isCompanyAdmin();
    }

    /**
     * Cabang yang membatasi user ini, atau NULL bila boleh melihat semua.
     */
    public static function restrictedBranchId(?User $user = null): ?int
    {
        $user ??= Auth::user();

        if (!$user instanceof User || self::seesAll($user)) {
            return null;
        }

        return (int) ($user->branch_id ?: self::headOffice((int) $user->company_id)->id);
    }

    public static function isRestricted(?User $user = null): bool
    {
        return self::restrictedBranchId($user) !== null;
    }

    /**
     * Semua cabang yang asetnya boleh dilihat (cabang sendiri + cabang yang
     * dilayani bila user teknisi). NULL = semua.
     *
     * @return array<int>|null
     */
    public static function accessibleBranchIds(?User $user = null): ?array
    {
        $user ??= Auth::user();
        $own = self::restrictedBranchId($user);

        if ($own === null) {
            return null;
        }

        $key = $user->id . ':' . $user->role_id . ':' . $own;

        if (array_key_exists($key, self::$accessible)) {
            return self::$accessible[$key];
        }

        $ids = [$own];

        // Teknisi (bukan Administrator) juga melihat aset cabang yang dilayaninya.
        if (!$user->isAdministrator() && $user->hasPermission('maintenance.edit')) {
            foreach (self::serviceMap((int) $user->company_id) as $branchId => $serviceBranchId) {
                if ($serviceBranchId === $own) {
                    $ids[] = (int) $branchId;
                }
            }
        }

        return self::$accessible[$key] = array_values(array_unique($ids));
    }

    /**
     * Boleh melihat / mengelola cabang ini?
     */
    public static function canAccessBranch(?int $branchId, ?User $user = null): bool
    {
        $restricted = self::restrictedBranchId($user);

        return $restricted === null || (int) $branchId === $restricted;
    }

    /**
     * Saring query user ke cabang user yang login (kantor pusat: tidak disaring).
     */
    public static function scopeUsers(Builder $query, ?User $viewer = null, string $column = 'users.branch_id'): Builder
    {
        $restricted = self::restrictedBranchId($viewer);

        return $restricted === null ? $query : $query->where($column, $restricted);
    }

    /**
     * Kantor Pusat perusahaan; dibuat otomatis bila belum ada.
     */
    public static function headOffice(int $companyId): Branch
    {
        if (isset(self::$headOffice[$companyId])) {
            return self::$headOffice[$companyId];
        }

        $branch = Branch::query()
            ->where('company_id', $companyId)
            ->where('is_head_office', true)
            ->orderBy('id')
            ->first();

        if (!$branch) {
            $trashed = Branch::withTrashed()
                ->where('company_id', $companyId)
                ->where('branch_code', 'PUSAT')
                ->first();

            if ($trashed) {
                $trashed->restore();
                $trashed->update(['is_head_office' => true, 'status' => 1]);
                $branch = $trashed;
            } else {
                $branch = Branch::create([
                    'company_id' => $companyId,
                    'branch_code' => 'PUSAT',
                    'branch_name' => 'Kantor Pusat',
                    'is_head_office' => true,
                    'status' => 1,
                ]);
            }
        }

        return self::$headOffice[$companyId] = $branch;
    }

    /**
     * Cabang default untuk aset / user baru: cabang user (bila dibatasi) atau Kantor Pusat.
     */
    public static function defaultBranchId(int $companyId, ?User $user = null): int
    {
        return self::restrictedBranchId($user) ?? (int) self::headOffice($companyId)->id;
    }

    /**
     * Cabang yang boleh dipilih user (untuk dropdown).
     *
     * @return Collection<int, Branch>
     */
    public static function options(int $companyId, ?User $user = null, bool $activeOnly = true): Collection
    {
        self::headOffice($companyId);

        $restricted = self::restrictedBranchId($user);

        return Branch::query()
            ->where('company_id', $companyId)
            ->when($activeOnly, fn ($q) => $q->where('status', 1))
            ->when($restricted !== null, fn ($q) => $q->whereKey($restricted))
            ->orderByDesc('is_head_office')
            ->orderBy('branch_name')
            ->get();
    }

    /**
     * Cabang milik perusahaan & boleh diakses user? Mengembalikan model atau NULL.
     */
    public static function find(int $companyId, mixed $branchId, ?User $user = null): ?Branch
    {
        if (!$branchId || !is_numeric($branchId)) {
            return null;
        }

        $branch = Branch::query()
            ->where('company_id', $companyId)
            ->whereKey((int) $branchId)
            ->first();

        if (!$branch || !self::canAccessBranch($branch->id, $user)) {
            return null;
        }

        return $branch;
    }

    /**
     * Perusahaan punya lebih dari satu cabang? (untuk menampilkan pilihan cabang)
     */
    public static function hasMultiple(int $companyId): bool
    {
        return Branch::query()->where('company_id', $companyId)->count() > 1;
    }

    /*
    |--------------------------------------------------------------------------
    | TEKNISI / MAINTENANCE
    |--------------------------------------------------------------------------
    */

    /**
     * Cabang yang teknisinya menangani maintenance aset di $branchId.
     */
    public static function serviceBranchId(int $companyId, ?int $branchId): int
    {
        $map = self::serviceMap($companyId);

        return $map[(int) $branchId] ?? (int) self::headOffice($companyId)->id;
    }

    /**
     * Peta cabang => cabang pelayan maintenance.
     *  1. service_branch_id bila diatur di data cabang;
     *  2. cabang sendiri bila punya teknisi aktif;
     *  3. selain itu Kantor Pusat.
     *
     * @return array<int, int>
     */
    public static function serviceMap(int $companyId): array
    {
        if (isset(self::$serviceMap[$companyId])) {
            return self::$serviceMap[$companyId];
        }

        $hq = (int) self::headOffice($companyId)->id;
        $withTechnicians = array_flip(self::branchesWithTechnicians($companyId));
        $map = [];

        foreach (Branch::query()->where('company_id', $companyId)->get(['id', 'service_branch_id']) as $branch) {
            $map[(int) $branch->id] = match (true) {
                $branch->service_branch_id !== null => (int) $branch->service_branch_id,
                isset($withTechnicians[(int) $branch->id]) => (int) $branch->id,
                default => $hq,
            };
        }

        return self::$serviceMap[$companyId] = $map;
    }

    /**
     * Cabang yang punya teknisi aktif (user aktif, role aktif non-Administrator
     * dengan izin maintenance.edit).
     *
     * @return array<int>
     */
    public static function branchesWithTechnicians(int $companyId): array
    {
        return DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->join('role_permissions', 'role_permissions.role_id', '=', 'roles.id')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('users.company_id', $companyId)
            ->where('users.status', 1)
            ->where('roles.status', 1)
            ->where('roles.role_code', '!=', 'administrator')
            ->where('permissions.permission_code', 'maintenance.edit')
            ->whereNotNull('users.branch_id')
            ->distinct()
            ->pluck('users.branch_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Administrator aktif di cabang tertentu; bila tidak ada -> Administrator Kantor Pusat.
     */
    public static function administrators(int $companyId, ?int $branchId = null): \Illuminate\Support\Collection
    {
        $admins = fn (int $id) => User::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->where('branch_id', $id)
            ->whereHas('role', fn ($r) => $r->where('role_code', 'administrator'))
            ->get();

        $hq = (int) self::headOffice($companyId)->id;

        if ($branchId && $branchId !== $hq) {
            $found = $admins($branchId);

            if ($found->isNotEmpty()) {
                return $found;
            }
        }

        return $admins($hq);
    }

    public static function flush(): void
    {
        self::$headOffice = [];
        self::$serviceMap = [];
        self::$seesAll = [];
        self::$accessible = [];
    }
}
