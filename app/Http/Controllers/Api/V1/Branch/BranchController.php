<?php

namespace App\Http\Controllers\Api\V1\Branch;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Branch;
use App\Models\Scopes\BranchScope;
use App\Services\Branch\BranchService;
use App\Support\ActionFailed;
use App\Support\BranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cabang untuk aplikasi mobile.
 *
 * GET    /branches          daftar cabang + kuota paket (dipakai juga untuk dropdown)
 * POST   /branches          tambah (branch.create) — 422 PLAN_LIMIT_REACHED bila kuota penuh
 * PUT    /branches/{id}     ubah   (branch.edit)
 * DELETE /branches/{id}     hapus  (branch.delete) — 422 IN_USE bila masih ada aset/user/mutasi
 */
class BranchController extends ApiController
{
    public function __construct(
        protected BranchService $branches
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = (int) $user->company_id;
        $restricted = BranchAccess::restrictedBranchId($user);

        BranchAccess::headOffice($companyId);

        $rows = Branch::query()
            ->where('company_id', $companyId)
            ->when(!$request->boolean('include_inactive'), fn ($q) => $q->where('status', 1))
            ->withCount('users')
            ->orderByDesc('is_head_office')
            ->orderBy('branch_name')
            ->get();

        $assetCounts = \App\Http\Controllers\Dashboard\BranchController::assetCountsByBranch($companyId);
        $rows->each(fn ($b) => $b->setAttribute('assets_count', (int) ($assetCounts[$b->id] ?? 0)));

        return $this->ok([
            'branches' => $rows->map(fn (Branch $b) => $this->present($b, $restricted))->values(),
            'quota' => $this->branches->quota(),
            // null = user kantor pusat (melihat semua cabang).
            'my_branch_id' => $restricted,
            'multi_branch' => $rows->count() > 1,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        try {
            $branch = $this->branches->create($request->user(), $data);
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        }

        return $this->ok(['branch' => $this->present($branch, null)], 'Cabang berhasil ditambahkan.', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $this->validated($request);

        try {
            $branch = $this->branches->find($request->user(), $id);
            $this->branches->update($request->user(), $branch, $data);
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        }

        return $this->ok(['branch' => $this->present($branch->fresh(), null)], 'Cabang berhasil diperbarui.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $branch = $this->branches->find($request->user(), $id);
            $this->branches->delete($request->user(), $branch);
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        }

        return $this->ok(null, 'Cabang berhasil dihapus.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'branch_name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'pic_name' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:0,1'],
            'service_branch_id' => ['nullable', 'integer'],
        ], [
            'branch_name.required' => 'Nama cabang wajib diisi.',
        ]);
    }

    public static function presentBranch(?Branch $b): ?array
    {
        return $b ? [
            'id' => $b->id,
            'code' => $b->branch_code,
            'name' => $b->branch_name,
            'is_head_office' => (bool) $b->is_head_office,
        ] : null;
    }

    protected function present(Branch $b, ?int $restricted): array
    {
        return self::presentBranch($b) + [
            'address' => $b->address,
            'phone' => $b->phone,
            'pic_name' => $b->pic_name,
            'status' => (int) $b->status,
            'assets_count' => (int) ($b->assets_count ?? 0),
            'users_count' => (int) ($b->users_count ?? 0),
            'is_mine' => $restricted !== null && $restricted === (int) $b->id,
            // NULL = otomatis (teknisi cabang sendiri, bila tidak ada -> Kantor Pusat).
            'service_branch_id' => $b->service_branch_id,
            // Cabang yang benar-benar menangani maintenance cabang ini saat ini.
            'effective_service_branch_id' => BranchAccess::serviceBranchId((int) $b->company_id, (int) $b->id),
            'has_technicians' => in_array((int) $b->id, BranchAccess::branchesWithTechnicians((int) $b->company_id), true),
        ];
    }
}
