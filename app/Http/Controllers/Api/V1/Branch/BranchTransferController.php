<?php

namespace App\Http\Controllers\Api\V1\Branch;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Asset;
use App\Models\BranchTransfer;
use App\Models\BranchTransferItem;
use App\Services\Branch\BranchTransferService;
use App\Support\ActionFailed;
use App\Support\BranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mutasi aset antar-cabang untuk aplikasi mobile.
 *
 * GET  /branch-transfers?status=sent|received|cancelled&q=     daftar (branch.transfer|branch.receive)
 * GET  /branch-transfers/assets?branch_id=&q=                   aset yang bisa dikirim (branch.transfer)
 * POST /branch-transfers                                        kirim (branch.transfer)
 * GET  /branch-transfers/{id}                                   detail + can_receive/can_cancel
 * POST /branch-transfers/{id}/receive                           terima (branch.receive, cabang tujuan)
 * POST /branch-transfers/{id}/cancel                            batalkan (branch.transfer, cabang asal)
 */
class BranchTransferController extends ApiController
{
    public function __construct(
        protected BranchTransferService $transfers
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = in_array($request->query('status'), array_keys(BranchTransfer::STATUS_LABELS), true) ? $request->query('status') : null;
        $term = trim((string) $request->query('q'));
        $base = $this->transfers->visibleQuery($user);

        $counts = (clone $base)->selectRaw('status, COUNT(*) AS c')->groupBy('status')->pluck('c', 'status');
        $restricted = BranchAccess::restrictedBranchId($user);

        $page = (clone $base)
            ->with(['fromBranch', 'toBranch', 'sender', 'receiver'])
            ->withCount('items')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('transfer_no', 'like', "%{$term}%")
                ->orWhereHas('items', fn ($i) => $i->where('asset_code', 'like', "%{$term}%")->orWhere('asset_name', 'like', "%{$term}%"))))
            ->orderByRaw("status = 'sent' DESC")
            ->latest('id')
            ->paginate($this->perPage($request));

        $response = $this->paginated('transfers', $page, fn (BranchTransfer $t) => $this->present($t, $user));
        $data = $response->getData(true);
        $data['data']['counts'] = [
            'sent' => (int) ($counts['sent'] ?? 0),
            'received' => (int) ($counts['received'] ?? 0),
            'cancelled' => (int) ($counts['cancelled'] ?? 0),
        ];
        // Jumlah mutasi masuk yang menunggu diterima user ini (untuk badge).
        $data['data']['incoming_pending'] = (clone $base)
            ->where('status', BranchTransfer::STATUS_SENT)
            ->when($restricted, fn ($q) => $q->where('to_branch_id', $restricted))
            ->count();

        return response()->json($data);
    }

    public function assets(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = (int) $user->company_id;
        $branch = BranchAccess::find($companyId, $request->query('branch_id') ?? BranchAccess::restrictedBranchId($user), $user);

        if (!$branch) {
            return $this->fail('Cabang asal tidak valid.', 'VALIDATION_ERROR');
        }

        $term = trim((string) $request->query('q'));

        $page = $this->transfers->movableAssets($companyId, $branch->id)
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('asset_code', 'like', "%{$term}%")
                ->orWhere('asset_name', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%")
                ->orWhere('serial_number', 'like', "%{$term}%")))
            ->with('responsibleUser:id,name')
            ->orderBy('asset_code')
            ->paginate($this->perPage($request, 50));

        return $this->paginated('assets', $page, fn (Asset $a) => [
            'id' => $a->id,
            'asset_code' => $a->asset_code,
            'asset_name' => $a->asset_name,
            'location' => $a->location,
            'holder' => $a->responsibleUser?->name,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_branch_id' => ['required', 'integer'],
            'to_branch_id' => ['required', 'integer'],
            'asset_ids' => ['required', 'array', 'min:1', 'max:200'],
            'asset_ids.*' => ['integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $transfer = $this->transfers->create($request->user(), $validated['from_branch_id'], $validated['to_branch_id'], $validated['asset_ids'], $validated['notes'] ?? null);
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        }

        return $this->ok(['transfer' => $this->detail($transfer, $request)], 'Mutasi dibuat. Menunggu konfirmasi cabang tujuan.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $transfer = $this->transfers->find($request->user(), $id);
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        }

        return $this->ok(['transfer' => $this->detail($transfer, $request)]);
    }

    public function receive(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'location' => ['nullable', 'string', 'max:255'],
            'receive_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $transfer = $this->transfers->find($request->user(), $id);
            $result = $this->transfers->receive($request->user(), $transfer, $validated['location'] ?? null, $validated['receive_notes'] ?? null);
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        }

        return $this->ok([
            'transfer' => $this->detail($transfer->fresh(), $request),
            'moved' => $result['moved'],
            'skipped' => $result['skipped'],
        ], $result['moved'] . ' aset diterima.');
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $transfer = $this->transfers->find($request->user(), $id);
            $this->transfers->cancel($request->user(), $transfer);
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        }

        return $this->ok(['transfer' => $this->detail($transfer->fresh(), $request)], 'Mutasi dibatalkan.');
    }

    protected function present(BranchTransfer $t, $user): array
    {
        return [
            'id' => $t->id,
            'transfer_no' => $t->transfer_no,
            'status' => $t->status,
            'status_label' => $t->statusLabel(),
            'from_branch' => BranchController::presentBranch($t->fromBranch),
            'to_branch' => BranchController::presentBranch($t->toBranch),
            'items_count' => (int) ($t->items_count ?? $t->items()->count()),
            'notes' => $t->notes,
            'receive_notes' => $t->receive_notes,
            'sent_at' => $t->sent_at?->toIso8601String(),
            'sent_by' => $t->sender?->name,
            'received_at' => $t->received_at?->toIso8601String(),
            'received_by' => $t->receiver?->name,
            'cancelled_at' => $t->cancelled_at?->toIso8601String(),
            'can_receive' => $this->transfers->canReceive($user, $t),
            'can_cancel' => $this->transfers->canCancel($user, $t),
        ];
    }

    protected function detail(BranchTransfer $t, Request $request): array
    {
        $t->load(['fromBranch', 'toBranch', 'sender', 'receiver', 'canceller', 'items.asset.branch']);

        return $this->present($t, $request->user()) + [
            'cancelled_by' => $t->canceller?->name,
            'items' => $t->items->map(fn (BranchTransferItem $i) => [
                'id' => $i->id,
                'asset_id' => $i->asset_id,
                'asset_code' => $i->asset_code,
                'asset_name' => $i->asset_name,
                'from_location' => $i->from_location,
                'current_branch' => BranchController::presentBranch($i->asset?->branch),
                'current_location' => $i->asset?->location,
                'asset_deleted' => !$i->asset || $i->asset->trashed(),
            ])->values(),
        ];
    }
}
