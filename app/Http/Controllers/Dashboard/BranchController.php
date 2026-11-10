<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchTransfer;
use App\Models\Scopes\BranchScope;
use App\Services\Branch\BranchService;
use App\Support\ActionFailed;
use App\Support\BranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Master cabang (web). Aturan & batas paket ada di BranchService.
 */
class BranchController extends Controller
{
    public function __construct(
        protected BranchService $branches
    ) {
    }

    public function index(): View
    {
        $user = Auth::user();
        $companyId = (int) $user->company_id;

        BranchAccess::headOffice($companyId);

        $branches = Branch::query()
            ->where('company_id', $companyId)
            ->with('serviceBranch')
            // Admin cabang hanya melihat cabangnya sendiri.
            ->when(BranchAccess::restrictedBranchId($user), fn ($q, $id) => $q->whereKey($id))
            ->withCount('users')
            ->orderByDesc('is_head_office')
            ->orderBy('branch_name')
            ->get();

        // Jumlah aset per cabang: satu query kelompok ber-index + cache.
        $assetCounts = self::assetCountsByBranch($companyId);
        $branches->each(fn ($b) => $b->setAttribute('assets_count', (int) ($assetCounts[$b->id] ?? 0)));

        $quota = $this->branches->quota();

        return view('dashboard.branch.index', [
            'branches' => $branches,
            'restrictedBranchId' => BranchAccess::restrictedBranchId($user),
            // Siapa yang menangani maintenance tiap cabang (hasil akhir, termasuk otomatis).
            'serviceMap' => BranchAccess::serviceMap($companyId),
            'withTechnicians' => BranchAccess::branchesWithTechnicians($companyId),
            'limit' => [
                'max' => (int) ($quota['max'] ?? 0),
                'used' => $quota['used'],
                'unlimited' => $quota['max'] === null,
                'can_add' => $quota['can_add'],
                'plan' => $quota['plan'],
            ],
            'pendingTransfers' => BranchTransfer::query()
                ->where('company_id', $companyId)
                ->where('status', BranchTransfer::STATUS_SENT)
                ->count(),
        ]);
    }

    /**
     * [branch_id => jumlah aset aktif/tidak aktif] (tanpa yang dihapuskan).
     */
    public static function assetCountsByBranch(int $companyId): array
    {
        return \App\Services\Asset\AssetStats::remember($companyId, 'branch-asset-counts', fn () => \App\Models\Asset::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('company_id', $companyId)
            ->inService()
            ->toBase()
            ->selectRaw('branch_id, COUNT(*) AS c')
            ->groupBy('branch_id')
            ->pluck('c', 'branch_id')
            ->all());
    }

    public function store(Request $request): JsonResponse
    {
        if ($invalid = $this->validateInput($request)) {
            return $invalid;
        }

        try {
            $branch = $this->branches->create(Auth::user(), $request->all());
        } catch (ActionFailed $e) {
            return $this->fail($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cabang "' . $branch->branch_name . '" berhasil ditambahkan.',
        ]);
    }

    public function edit(int $branch): JsonResponse
    {
        try {
            $model = $this->branches->find(Auth::user(), $branch);
        } catch (ActionFailed $e) {
            return $this->fail($e);
        }

        return response()->json($model->only(['id', 'branch_code', 'branch_name', 'address', 'phone', 'pic_name', 'status', 'is_head_office', 'service_branch_id']));
    }

    public function update(Request $request, int $branch): JsonResponse
    {
        if ($invalid = $this->validateInput($request)) {
            return $invalid;
        }

        try {
            $model = $this->branches->find(Auth::user(), $branch);
            $this->branches->update(Auth::user(), $model, $request->all());
        } catch (ActionFailed $e) {
            return $this->fail($e);
        }

        return response()->json(['success' => true, 'message' => 'Cabang berhasil diperbarui.']);
    }

    public function destroy(int $branch): JsonResponse
    {
        try {
            $model = $this->branches->find(Auth::user(), $branch);
            $this->branches->delete(Auth::user(), $model);
        } catch (ActionFailed $e) {
            return $this->fail($e);
        }

        return response()->json(['success' => true, 'message' => 'Cabang berhasil dihapus. Kuota cabang bertambah kembali.']);
    }

    protected function validateInput(Request $request): ?JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'pic_name' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:0,1'],
            'service_branch_id' => ['nullable', 'integer'],
        ], [
            'branch_name.required' => 'Nama cabang wajib diisi.',
        ]);

        return $validator->fails()
            ? response()->json(['success' => false, 'errors' => $validator->errors()], 422)
            : null;
    }

    protected function fail(ActionFailed $e): JsonResponse
    {
        if ($e->errorCode === 'PLAN_LIMIT_REACHED') {
            // Popup "Batas cabang tercapai" + tombol upgrade.
            return response()->json([
                'success' => false,
                'limit_reached' => true,
                'errors' => ['plan_limit' => [$e->getMessage()]],
            ], 422);
        }

        return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status);
    }
}
