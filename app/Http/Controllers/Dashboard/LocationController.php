<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Scopes\BranchScope;
use App\Services\Asset\LocationService;
use App\Support\ActionFailed;
use App\Support\BranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Master lokasi per cabang (ruangan / lantai / gudang).
 */
class LocationController extends Controller
{
    public function __construct(
        protected LocationService $locations
    ) {
    }

    public function index(Request $request): View
    {
        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $restricted = BranchAccess::restrictedBranchId($user);
        $branchId = $restricted ?? ($request->filled('branch_id') ? (int) $request->branch_id : null);

        $rows = Location::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with('branch:id,branch_name,is_head_office')
            ->orderBy('branch_id')
            ->orderBy('name')
            ->get();

        // Jumlah aset per lokasi: satu query kelompok ber-index + cache.
        $counts = \App\Services\Asset\AssetStats::remember($companyId, 'location-asset-counts', fn () => \App\Models\Asset::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('company_id', $companyId)
            ->toBase()
            ->whereNull('deleted_at')
            ->whereNotNull('location_id')
            ->selectRaw('location_id, COUNT(*) AS c')
            ->groupBy('location_id')
            ->pluck('c', 'location_id')
            ->all());
        $rows->each(fn ($l) => $l->setAttribute('assets_count', (int) ($counts[$l->id] ?? 0)));

        return view('dashboard.location.index', [
            'locations' => $rows,
            'branches' => BranchAccess::options($companyId, $user),
            'branchId' => $branchId,
            'restricted' => $restricted,
        ]);
    }

    /**
     * Daftar lokasi aktif sebuah cabang (AJAX untuk dropdown).
     */
    public function options(Request $request): JsonResponse
    {
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;

        return response()->json([
            'data' => $this->locations->options(Auth::user(), $branchId)
                ->map(fn (Location $l) => ['id' => $l->id, 'name' => $l->name, 'branch_id' => $l->branch_id])
                ->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($invalid = $this->validateInput($request, true)) {
            return $invalid;
        }

        try {
            $location = $this->locations->create(Auth::user(), $request->all());
        } catch (ActionFailed $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lokasi "' . $location->name . '" ditambahkan.',
            'data' => ['id' => $location->id, 'name' => $location->name, 'branch_id' => $location->branch_id],
        ]);
    }

    public function update(Request $request, int $location): JsonResponse
    {
        if ($invalid = $this->validateInput($request, false)) {
            return $invalid;
        }

        try {
            $model = $this->locations->find(Auth::user(), $location);
            $this->locations->update(Auth::user(), $model, $request->all());
        } catch (ActionFailed $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status);
        }

        return response()->json(['success' => true, 'message' => __('Lokasi diperbarui.')]);
    }

    public function destroy(int $location): JsonResponse
    {
        try {
            $model = $this->locations->find(Auth::user(), $location);
            $this->locations->delete(Auth::user(), $model);
        } catch (ActionFailed $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status);
        }

        return response()->json(['success' => true, 'message' => __('Lokasi dihapus.')]);
    }

    protected function validateInput(Request $request, bool $creating): ?JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:150'],
            'branch_id' => [$creating ? 'nullable' : 'sometimes', 'integer'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:0,1'],
        ], [
            'name.required' => __('Nama lokasi wajib diisi.'),
        ]);

        return $validator->fails()
            ? response()->json(['success' => false, 'message' => $validator->errors()->first()], 422)
            : null;
    }
}
