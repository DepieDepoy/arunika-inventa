<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Branch;
use App\Models\BranchTransfer;
use App\Services\Branch\BranchTransferService;
use App\Support\ActionFailed;
use App\Support\BranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Mutasi aset antar-cabang (web). Aturan ada di BranchTransferService.
 */
class BranchTransferController extends Controller
{
    public function __construct(
        protected BranchTransferService $transfers
    ) {
    }

    public function index(Request $request): View
    {
        $user = Auth::user();
        $status = in_array($request->status, array_keys(BranchTransfer::STATUS_LABELS), true) ? $request->status : null;
        $base = $this->transfers->visibleQuery($user);

        $counts = (clone $base)->selectRaw('status, COUNT(*) AS c')->groupBy('status')->pluck('c', 'status');

        $transfers = (clone $base)
            ->with(['fromBranch', 'toBranch', 'sender', 'receiver'])
            ->withCount('items')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('transfer_no', 'like', '%' . $request->q . '%')
                ->orWhereHas('items', fn ($i) => $i->where('asset_code', 'like', '%' . $request->q . '%')->orWhere('asset_name', 'like', '%' . $request->q . '%'))))
            ->orderByRaw("status = 'sent' DESC")
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.branch-transfer.index', [
            'transfers' => $transfers,
            'counts' => $counts,
            'status' => $status,
            'restricted' => BranchAccess::restrictedBranchId($user),
        ]);
    }

    public function create(): View|RedirectResponse
    {
        $user = Auth::user();
        $companyId = (int) $user->company_id;

        if (!BranchAccess::hasMultiple($companyId)) {
            return redirect()->route('branches.index')->with('error', 'Tambahkan minimal satu cabang lain sebelum membuat mutasi aset.');
        }

        return view('dashboard.branch-transfer.create', [
            'fromOptions' => BranchAccess::options($companyId, $user),
            'toOptions' => Branch::query()->where('company_id', $companyId)->where('status', 1)
                ->orderByDesc('is_head_office')->orderBy('branch_name')->get(),
            'restricted' => BranchAccess::restrictedBranchId($user),
        ]);
    }

    /**
     * Pencarian aset di cabang asal yang bisa dimutasi (AJAX).
     */
    public function assets(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branch = BranchAccess::find($companyId, $request->branch_id, $user);

        if (!$branch) {
            return response()->json(['data' => []]);
        }

        $term = trim((string) $request->q);

        $assets = $this->transfers->movableAssets($companyId, $branch->id)
            ->when($term !== '', fn ($q) => \App\Services\Asset\AssetSearch::apply($q, $companyId, $term))
            ->with('responsibleUser:id,name')
            ->orderBy('asset_code')
            ->limit(50)
            ->get(['id', 'asset_code', 'asset_name', 'location', 'responsible_user_id']);

        return response()->json([
            'data' => $assets->map(fn (Asset $a) => [
                'id' => $a->id,
                'code' => $a->asset_code,
                'name' => $a->asset_name,
                'location' => $a->location,
                'holder' => $a->responsibleUser?->name,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from_branch_id' => ['required', 'integer'],
            'to_branch_id' => ['required', 'integer'],
            'asset_ids' => ['required', 'array', 'min:1', 'max:200'],
            'asset_ids.*' => ['integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'asset_ids.required' => 'Pilih minimal satu aset yang akan dikirim.',
            'asset_ids.max' => 'Maksimal 200 aset per mutasi.',
        ]);

        try {
            $transfer = $this->transfers->create(
                Auth::user(),
                $validated['from_branch_id'],
                $validated['to_branch_id'],
                $validated['asset_ids'],
                $validated['notes'] ?? null
            );
        } catch (ActionFailed $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $transfer->load('toBranch');

        return redirect()->route('branch-transfers.show', $transfer->id)
            ->with('success', 'Mutasi ' . $transfer->transfer_no . ' dibuat. ' . count($validated['asset_ids']) . ' aset menunggu konfirmasi diterima oleh ' . $transfer->toBranch?->branch_name . '.');
    }

    public function show(int $transfer): View
    {
        $user = Auth::user();

        try {
            $transfer = $this->transfers->find($user, $transfer);
        } catch (ActionFailed) {
            abort(404);
        }

        $transfer->load(['fromBranch', 'toBranch', 'sender', 'receiver', 'canceller', 'items.asset.branch']);

        return view('dashboard.branch-transfer.show', [
            'transfer' => $transfer,
            'canReceive' => $this->transfers->canReceive($user, $transfer),
            'canCancel' => $this->transfers->canCancel($user, $transfer),
        ]);
    }

    public function receive(Request $request, int $transfer): RedirectResponse
    {
        $validated = $request->validate([
            'location' => ['nullable', 'string', 'max:255'],
            'receive_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $model = $this->transfers->find(Auth::user(), $transfer);
            $result = $this->transfers->receive(Auth::user(), $model, $validated['location'] ?? null, $validated['receive_notes'] ?? null);
        } catch (ActionFailed $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('branch-transfers.show', $model->id)
            ->with('success', $result['moved'] . ' aset diterima dan kini tercatat di ' . $model->toBranch?->branch_name . '.'
                . ($result['skipped'] > 0 ? " {$result['skipped']} aset dilewati karena sudah tidak ada di cabang asal." : ''));
    }

    public function cancel(int $transfer): RedirectResponse
    {
        try {
            $model = $this->transfers->find(Auth::user(), $transfer);
            $this->transfers->cancel(Auth::user(), $model);
        } catch (ActionFailed $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Mutasi ' . $model->transfer_no . ' dibatalkan. Aset tetap di cabang asal.');
    }
}
