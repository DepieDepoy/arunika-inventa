<?php

namespace App\Http\Controllers\Api\V1\Asset;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\Api\V1\Branch\BranchController;
use App\Http\Controllers\Dashboard\AssetAuditController as WebAuditController;
use App\Models\AssetAudit;
use App\Models\AssetAuditItem;
use App\Services\Asset\AssetAuditService;
use App\Services\Asset\AssetLifecycleService;
use App\Support\ActionFailed;
use App\Support\BranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Audit Fisik Aset untuk aplikasi mobile (scan QR pakai kamera HP).
 *
 * GET  /asset-audits?status=in_progress|completed|cancelled      daftar (asset_audit.view|scan)
 * GET  /asset-audits/options                                      data form buat audit
 * POST /asset-audits                                              buat sesi (asset_audit.create)
 * GET  /asset-audits/{id}                                         detail + ringkasan
 * GET  /asset-audits/{id}/items?filter=&q=                        daftar aset (paginasi)
 * POST /asset-audits/{id}/scan                                    scan QR / kode (asset_audit.scan)
 * POST /asset-audits/{id}/items/{itemId}                          tandai manual found|missing|reset
 * POST /asset-audits/{id}/complete                                selesaikan (asset_audit.create)
 * POST /asset-audits/{id}/cancel                                  batalkan (asset_audit.create)
 * GET  /asset-audits/{id}/export                                  unduh Excel (asset_audit.view)
 */
class AssetAuditController extends ApiController
{
    public function __construct(
        protected AssetAuditService $audits,
        protected AssetLifecycleService $lifecycle
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $status = in_array($request->query('status'), array_keys(AssetAudit::STATUS_LABELS), true) ? $request->query('status') : null;

        $page = $this->audits->visibleQuery($request->user())
            ->with(['branch', 'starter'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByRaw("status = 'in_progress' DESC")
            ->latest('id')
            ->paginate($this->perPage($request));

        return $this->paginated('audits', $page, fn (AssetAudit $a) => $this->present($a, $request));
    }

    public function options(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = (int) $user->company_id;

        return $this->ok([
            'branches' => BranchAccess::options($companyId, $user)->map(fn ($b) => BranchController::presentBranch($b))->values(),
            // null = boleh pilih "Semua cabang"; terisi = cabang user (wajib).
            'my_branch_id' => BranchAccess::restrictedBranchId($user),
            'multi_branch' => BranchAccess::hasMultiple($companyId),
            'locations' => $this->lifecycle->knownLocations($companyId),
            'conditions' => collect(AssetAuditItem::CONDITIONS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'filters' => [
                ['key' => 'all', 'label' => 'Total aset'],
                ['key' => 'pending', 'label' => 'Belum dicek'],
                ['key' => 'found', 'label' => 'Ditemukan'],
                ['key' => 'misplaced', 'label' => 'Salah lokasi'],
                ['key' => 'damaged', 'label' => 'Rusak'],
                ['key' => 'missing', 'label' => 'Tidak ditemukan'],
                ['key' => 'extra', 'label' => 'Di luar daftar'],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'branch_id' => ['nullable', 'integer'],
            'location_filter' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'title.required' => 'Judul audit wajib diisi.',
        ]);

        try {
            $audit = $this->audits->create($request->user(), $validated['title'], $validated['branch_id'] ?? null, $validated['location_filter'] ?? null, $validated['notes'] ?? null);
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        }

        return $this->ok(['audit' => $this->present($audit, $request)], 'Audit dimulai.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $audit = $this->audits->find($request->user(), $id);
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        }

        return $this->ok(['audit' => $this->present($audit, $request)]);
    }

    public function items(Request $request, int $id): JsonResponse
    {
        try {
            $audit = $this->audits->find($request->user(), $id);
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        }

        $filter = in_array($request->query('filter'), AssetAuditService::FILTERS, true) ? $request->query('filter') : 'all';

        $page = $this->audits->itemsQuery($audit, $filter, $request->query('q'))
            ->with('checker:id,name')
            ->paginate($this->perPage($request, 50));

        return $this->paginated('items', $page, fn (AssetAuditItem $i) => $this->audits->itemPayload($i));
    }

    public function scan(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
            'condition' => ['nullable', 'in:' . implode(',', array_keys(AssetAuditItem::CONDITIONS))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $audit = $this->audits->find($request->user(), $id);
            $result = $this->audits->scan($request->user(), $audit, $validated['code'], $validated['location'] ?? null, $validated['condition'] ?? null, $validated['notes'] ?? null);
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        }

        return $this->ok([
            'item' => $this->audits->itemPayload($result['item']->load('checker:id,name')),
            'already_scanned' => $result['already'],
            'summary' => $audit->summary(),
        ], $result['message']);
    }

    public function mark(Request $request, int $id, int $itemId): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:found,missing,reset'],
        ]);

        try {
            $audit = $this->audits->find($request->user(), $id);
            $item = $this->audits->mark($request->user(), $audit, $itemId, $validated['action']);
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        }

        return $this->ok([
            'item' => $item ? $this->audits->itemPayload($item->load('checker:id,name')) : null,
            'removed' => $item === null,
            'summary' => $audit->summary(),
        ]);
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        try {
            $audit = $this->audits->find($request->user(), $id);
            $result = $this->audits->complete($request->user(), $audit, $request->boolean('apply_locations'));
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        }

        return $this->ok([
            'audit' => $this->present($audit->fresh(), $request),
            'locations_updated' => $result['locations_updated'],
        ], 'Audit selesai.');
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $audit = $this->audits->find($request->user(), $id);
            $this->audits->cancel($request->user(), $audit);
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        }

        return $this->ok(['audit' => $this->present($audit->fresh(), $request)], 'Audit dibatalkan.');
    }

    public function export(Request $request, int $id): Response
    {
        try {
            $audit = $this->audits->find($request->user(), $id);
        } catch (ActionFailed $e) {
            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        }

        return WebAuditController::excel($audit)->download('Vasetra-Audit-' . $audit->audit_no);
    }

    protected function present(AssetAudit $a, Request $request): array
    {
        $a->loadMissing(['branch', 'starter', 'completer']);
        $user = $request->user();

        return [
            'id' => $a->id,
            'audit_no' => $a->audit_no,
            'title' => $a->title,
            'status' => $a->status,
            'status_label' => $a->statusLabel(),
            'branch' => BranchController::presentBranch($a->branch),
            'location_filter' => $a->location_filter,
            'notes' => $a->notes,
            'locations_applied' => (bool) $a->locations_applied,
            'started_by' => $a->starter?->name,
            'created_at' => $a->created_at?->toIso8601String(),
            'completed_by' => $a->completer?->name,
            'completed_at' => $a->completed_at?->toIso8601String(),
            'summary' => $a->summary(),
            'can_scan' => $a->isOpen() && $user->hasPermission('asset_audit.scan'),
            'can_manage' => $a->isOpen() && $user->hasPermission('asset_audit.create'),
        ];
    }
}
