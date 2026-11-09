<?php

namespace App\Services\Branch;

use App\Helpers\CodeHelper;
use App\Helpers\PlanLimitHelper;
use App\Models\Branch;
use App\Models\BranchTransfer;
use App\Models\Scopes\BranchScope;
use App\Models\User;
use App\Support\ActionFailed;
use App\Support\BranchAccess;

/**
 * Kelola master cabang (dipakai web & API mobile).
 * Jumlah cabang dibatasi paket (plans.max_branches); Kantor Pusat ikut dihitung.
 */
class BranchService
{
    /**
     * Kuota cabang perusahaan user yang login.
     *
     * @return array{used:int, max:?int, remaining:?int, can_add:bool, plan:?string}
     */
    public function quota(): array
    {
        $max = PlanLimitHelper::maxBranches(); // 0 = tanpa batas
        $used = PlanLimitHelper::currentBranches();

        return [
            'used' => $used,
            'max' => $max === 0 ? null : $max,
            'remaining' => $max === 0 ? null : max(0, $max - $used),
            'can_add' => PlanLimitHelper::canAddBranches(1),
            'plan' => PlanLimitHelper::subscription()?->plan?->plan_name,
        ];
    }

    public function create(User $user, array $data): Branch
    {
        $companyId = (int) $user->company_id;

        if (BranchAccess::isRestricted($user)) {
            throw new ActionFailed('Hanya pengguna kantor pusat yang dapat menambah cabang.', 'FORBIDDEN', 403);
        }

        BranchAccess::headOffice($companyId);

        if (!PlanLimitHelper::canAddBranches(1)) {
            $limit = PlanLimitHelper::maxBranches();
            $current = PlanLimitHelper::currentBranches();

            throw new ActionFailed(
                "Batas cabang pada paket Anda adalah {$limit} cabang (termasuk Kantor Pusat). "
                    . "Saat ini sudah terdapat {$current} cabang. Silakan upgrade subscription untuk menambah cabang.",
                'PLAN_LIMIT_REACHED'
            );
        }

        $this->ensureNameFree($companyId, $data['branch_name'] ?? '');

        return Branch::create([
            'company_id' => $companyId,
            'branch_code' => CodeHelper::generateNumber('CB-', Branch::class, 'branch_code', $companyId),
            'branch_name' => trim((string) $data['branch_name']),
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'pic_name' => $data['pic_name'] ?? null,
            'is_head_office' => false,
            'service_branch_id' => $this->serviceBranch($companyId, $data['service_branch_id'] ?? null),
            'status' => (int) ($data['status'] ?? 1),
        ]);
    }

    public function update(User $user, Branch $branch, array $data): Branch
    {
        $this->authorize($user, $branch);
        $this->ensureNameFree((int) $branch->company_id, $data['branch_name'] ?? '', $branch->id);

        $status = (int) ($data['status'] ?? $branch->status);

        if ($branch->is_head_office && $status !== 1) {
            throw new ActionFailed('Kantor Pusat tidak bisa dinonaktifkan.');
        }

        $branch->update([
            'branch_name' => trim((string) $data['branch_name']),
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'pic_name' => $data['pic_name'] ?? null,
            'service_branch_id' => $this->serviceBranch((int) $branch->company_id, $data['service_branch_id'] ?? null, $branch->id),
            'status' => $status,
        ]);

        return $branch;
    }

    public function delete(User $user, Branch $branch): void
    {
        $this->authorize($user, $branch);

        if (BranchAccess::isRestricted($user)) {
            throw new ActionFailed('Hanya pengguna kantor pusat yang dapat menghapus cabang.', 'FORBIDDEN', 403);
        }

        if ($branch->is_head_office) {
            throw new ActionFailed('Kantor Pusat tidak bisa dihapus.', 'IN_USE');
        }

        $assets = $branch->assets()->withoutGlobalScope(BranchScope::class)->count();

        if ($assets > 0) {
            throw new ActionFailed("Cabang masih memiliki {$assets} aset. Pindahkan dulu asetnya lewat Mutasi Aset.", 'IN_USE');
        }

        if ($branch->users()->count() > 0) {
            throw new ActionFailed('Cabang masih dipakai oleh user. Ubah cabang user tersebut terlebih dahulu.', 'IN_USE');
        }

        $pending = BranchTransfer::query()
            ->where('status', BranchTransfer::STATUS_SENT)
            ->where(fn ($q) => $q->where('from_branch_id', $branch->id)->orWhere('to_branch_id', $branch->id))
            ->exists();

        if ($pending) {
            throw new ActionFailed('Masih ada mutasi aset yang belum diterima untuk cabang ini.', 'IN_USE');
        }

        $branch->delete();
    }

    /**
     * Cabang milik perusahaan user & boleh dikelola (user cabang: hanya cabangnya).
     */
    public function find(User $user, int $id): Branch
    {
        $branch = Branch::query()->where('company_id', $user->company_id)->find($id);

        if (!$branch) {
            throw new ActionFailed('Cabang tidak ditemukan.', 'NOT_FOUND', 404);
        }

        $this->authorize($user, $branch);

        return $branch;
    }

    protected function authorize(User $user, Branch $branch): void
    {
        if ((int) $branch->company_id !== (int) $user->company_id) {
            throw new ActionFailed('Cabang tidak ditemukan.', 'NOT_FOUND', 404);
        }

        if (!BranchAccess::canAccessBranch($branch->id, $user)) {
            throw new ActionFailed('Anda hanya dapat mengelola cabang Anda sendiri.', 'FORBIDDEN', 403);
        }
    }

    /**
     * Cabang pelayan maintenance (harus cabang aktif di perusahaan yang sama, bukan dirinya).
     */
    protected function serviceBranch(int $companyId, mixed $id, ?int $selfId = null): ?int
    {
        if (!$id || !is_numeric($id) || (int) $id === (int) $selfId) {
            return null;
        }

        $exists = Branch::query()->where('company_id', $companyId)->where('status', 1)->whereKey((int) $id)->exists();

        if (!$exists) {
            throw new ActionFailed('Cabang pelayan maintenance tidak valid.');
        }

        return (int) $id;
    }

    protected function ensureNameFree(int $companyId, string $name, ?int $ignoreId = null): void
    {
        $taken = Branch::query()
            ->where('company_id', $companyId)
            ->whereRaw('LOWER(branch_name) = ?', [mb_strtolower(trim($name))])
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        if ($taken) {
            throw new ActionFailed('Nama cabang sudah dipakai.');
        }
    }
}
