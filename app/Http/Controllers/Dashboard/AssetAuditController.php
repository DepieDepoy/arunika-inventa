<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AssetAudit;
use App\Models\AssetAuditItem;
use App\Models\Location;
use App\Services\Asset\AssetAuditService;
use App\Services\Reports\SpreadsheetExporter;
use App\Support\ActionFailed;
use App\Support\BranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Audit Fisik Aset (web). Aturan ada di AssetAuditService.
 */
class AssetAuditController extends Controller
{
    public function __construct(
        protected AssetAuditService $audits
    ) {
    }

    public function index(): View
    {
        $user = Auth::user();
        $companyId = (int) $user->company_id;

        $audits = $this->audits->visibleQuery($user)
            ->with(['branch', 'starter'])
            ->withCount('assignees')
            ->orderByRaw("status = 'in_progress' DESC")
            ->latest('id')
            ->paginate(15);

        $audits->getCollection()->each(fn (AssetAudit $a) => $a->setAttribute('stats', $a->summary()));

        return view('dashboard.asset-audit.index', [
            'audits' => $audits,
            'branches' => BranchAccess::options($companyId, $user),
            'restricted' => BranchAccess::restrictedBranchId($user),
            'multiBranch' => BranchAccess::hasMultiple($companyId),
            'locations' => $this->locationOptions($user),
            'candidates' => $user->hasPermission('asset_audit.create') ? $this->audits->assigneeCandidates($user) : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'branch_id' => ['nullable', 'integer'],
            'location_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'assignees' => ['nullable', 'array', 'max:50'],
            'assignees.*.user_id' => ['nullable', 'integer'],
            'assignees.*.location_id' => ['nullable', 'integer'],
        ], [
            'title.required' => __('Judul audit wajib diisi.'),
        ]);

        try {
            $audit = $this->audits->create(
                Auth::user(),
                $validated['title'],
                $validated['branch_id'] ?? null,
                $validated['location_id'] ?? null,
                $validated['notes'] ?? null,
                $validated['assignees'] ?? []
            );
        } catch (ActionFailed $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $team = $audit->assignees()->distinct('user_id')->count('user_id');

        return redirect()->route('asset-audits.show', $audit->id)
            ->with('success', 'Audit ' . $audit->audit_no . ' dimulai dengan ' . $audit->items()->count() . ' aset'
                . ($team ? " dan {$team} petugas (sudah diberi notifikasi)." : '. Silakan mulai scan QR aset.'));
    }

    public function show(Request $request, int $audit): View
    {
        $audit = $this->findOr404($audit);
        $audit->load(['branch', 'starter', 'completer', 'location']);
        $user = Auth::user();

        $isAssignee = $this->audits->isAssignee($user, $audit);
        $default = $isAssignee && $audit->isOpen() ? 'mine' : 'all';
        $filter = in_array($request->get('f'), AssetAuditService::FILTERS, true) ? $request->get('f') : $default;

        $items = $this->audits->itemsQuery($audit, $filter, $request->get('q'), $user)
            ->with(['checker:id,name', 'expectedBranch:id,branch_name'])
            ->paginate(50)
            ->withQueryString();

        // Lokasi untuk scan & tandai massal: lokasi cabang audit.
        $scanLocations = Location::query()
            ->where('company_id', $audit->company_id)
            ->where('status', 1)
            ->when($audit->branch_id, fn ($q) => $q->where('branch_id', $audit->branch_id))
            ->with('branch:id,branch_name')
            ->orderBy('branch_id')->orderBy('name')
            ->get();

        $myAreas = $isAssignee ? $audit->assignees()->where('user_id', $user->id)->pluck('location_id') : collect();

        return view('dashboard.asset-audit.show', [
            'audit' => $audit,
            'items' => $items,
            'stats' => $audit->summary(),
            'filter' => $filter,
            'isAssignee' => $isAssignee,
            'myAreas' => $myAreas,
            'team' => $this->audits->teamProgress($audit),
            'canScan' => $this->audits->canScan($user, $audit),
            'canManage' => $this->audits->canManage($user, $audit),
            'scanLocations' => $scanLocations,
        ]);
    }

    /**
     * Ringkasan terbaru (dipakai halaman audit untuk update otomatis saat
     * beberapa petugas scan bersamaan).
     */
    public function summary(int $audit): JsonResponse
    {
        try {
            $model = $this->audits->find(Auth::user(), $audit);
        } catch (ActionFailed $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status);
        }

        return response()->json(['success' => true, 'stats' => $model->summary(), 'team' => $this->audits->teamProgress($model)]);
    }

    public function scan(Request $request, int $audit): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:500'],
            'location_id' => ['nullable', 'integer'],
            'condition' => ['nullable', 'in:' . implode(',', array_keys(AssetAuditItem::CONDITIONS))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $model = $this->audits->find(Auth::user(), $audit);
            $result = $this->audits->scan(Auth::user(), $model, $validated['code'], $validated['location_id'] ?? null, $validated['condition'] ?? null, $validated['notes'] ?? null);
        } catch (ActionFailed $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status);
        }

        $item = $this->audits->itemPayload($result['item']);
        // Tampilan web memakai jam & label kondisi.
        $item['checked_at'] = $result['item']->checked_at?->format('H:i');
        $item['condition'] = $result['item']->conditionLabel();

        return response()->json([
            'success' => true,
            'already' => $result['already'],
            'item' => $item,
            'stats' => $model->summary(),
            'message' => $result['message'],
        ]);
    }

    public function mark(Request $request, int $audit, int $item): JsonResponse
    {
        try {
            $model = $this->audits->find(Auth::user(), $audit);
            $row = $this->audits->mark(Auth::user(), $model, $item, (string) $request->input('action'));
        } catch (ActionFailed $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status);
        }

        if (!$row) {
            return response()->json(['success' => true, 'removed' => true, 'stats' => $model->summary()]);
        }

        $payload = $this->audits->itemPayload($row);
        $payload['checked_at'] = $row->checked_at?->format('H:i');

        return response()->json(['success' => true, 'item' => $payload, 'stats' => $model->summary()]);
    }

    public function markLocation(Request $request, int $audit): RedirectResponse
    {
        try {
            $model = $this->audits->find(Auth::user(), $audit);
            $count = $this->audits->markLocationFound(Auth::user(), $model, $request->input('location_id'));
        } catch (ActionFailed $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $count . ' aset yang belum dicek di lokasi tersebut ditandai ditemukan.');
    }

    public function complete(Request $request, int $audit): RedirectResponse
    {
        try {
            $model = $this->audits->find(Auth::user(), $audit);
            $result = $this->audits->complete(Auth::user(), $model, $request->boolean('apply_locations'));
        } catch (ActionFailed $e) {
            return back()->with('error', $e->getMessage());
        }

        $s = $result['summary'];

        return redirect()->route('asset-audits.show', ['audit' => $model->id, 'f' => 'all'])->with(
            'success',
            "Audit selesai: {$s['found']} ditemukan, {$s['missing']} tidak ditemukan, {$s['misplaced']} salah lokasi, {$s['other_branch']} beda cabang."
                . ($request->boolean('apply_locations') ? " Lokasi {$result['locations_updated']} aset diperbarui." : '')
        );
    }

    public function cancel(int $audit): RedirectResponse
    {
        try {
            $model = $this->audits->find(Auth::user(), $audit);
            $this->audits->cancel(Auth::user(), $model);
        } catch (ActionFailed $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('asset-audits.index')->with('success', 'Audit ' . $model->audit_no . ' dibatalkan.');
    }

    public function export(int $audit)
    {
        $audit = $this->findOr404($audit);

        return self::excel($audit)->download('Vasetra-Audit-' . $audit->audit_no);
    }

    /**
     * Laporan Excel audit (dipakai juga oleh API mobile).
     */
    public static function excel(AssetAudit $audit): SpreadsheetExporter
    {
        $audit->loadMissing('branch');

        $items = $audit->items()
            ->with(['checker:id,name', 'expectedBranch:id,branch_name'])
            ->orderByRaw("FIELD(result, 'missing', 'found')")
            ->orderBy('expected_location')
            ->orderBy('asset_code')
            ->get();

        $stateLabels = ['pending' => 'Belum dicek', 'found' => 'Ditemukan', 'misplaced' => 'Salah lokasi', 'missing' => 'Tidak ditemukan', 'extra' => 'Di luar daftar', 'other_branch' => 'Beda cabang'];
        $s = $audit->summary();

        $excel = SpreadsheetExporter::forCurrentUser();
        $excel->table('Audit Fisik', 'Audit Fisik Aset ' . $audit->audit_no . ' — ' . $audit->title, [
            ['key' => 'asset_code', 'label' => 'Kode aset', 'width' => 16],
            ['key' => 'asset_name', 'label' => 'Nama aset', 'width' => 30],
            ['label' => 'Hasil', 'width' => 16, 'value' => fn (AssetAuditItem $i) => $stateLabels[$i->state()] ?? $i->state()],
            ['label' => 'Cabang aset', 'width' => 18, 'value' => fn (AssetAuditItem $i) => $i->expectedBranch?->branch_name ?? ''],
            ['key' => 'expected_location', 'label' => 'Lokasi tercatat', 'width' => 22],
            ['key' => 'found_location', 'label' => 'Lokasi ditemukan', 'width' => 22],
            ['label' => 'Kondisi', 'width' => 14, 'value' => fn (AssetAuditItem $i) => $i->conditionLabel() ?? ''],
            ['key' => 'notes', 'label' => 'Catatan', 'width' => 28],
            ['label' => 'Dicek oleh', 'width' => 18, 'value' => fn (AssetAuditItem $i) => $i->checker?->name ?? ''],
            ['label' => 'Waktu cek', 'width' => 18, 'value' => fn (AssetAuditItem $i) => $i->checked_at?->format('d/m/Y H:i') ?? ''],
        ], $items, sprintf(
            '%s%s · %s · %d aset: %d ditemukan, %d tidak ditemukan, %d salah lokasi, %d rusak, %d beda cabang, %d di luar daftar',
            $audit->branch?->branch_name ?? 'Semua cabang',
            $audit->location_filter ? ' / ' . $audit->location_filter : '',
            $audit->statusLabel(),
            $s['total'], $s['found'], $s['missing'], $s['misplaced'], $s['damaged'], $s['other_branch'], $s['extra']
        ));

        return $excel;
    }

    /**
     * Lokasi per cabang untuk form buat audit: [branch_id => [{id, name}]].
     */
    protected function locationOptions($user): array
    {
        $restricted = BranchAccess::restrictedBranchId($user);

        return Location::query()
            ->where('company_id', $user->company_id)
            ->where('status', 1)
            ->when($restricted, fn ($q) => $q->where('branch_id', $restricted))
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id'])
            ->groupBy('branch_id')
            ->map(fn ($l) => $l->map(fn ($x) => ['id' => $x->id, 'name' => $x->name])->values())
            ->all();
    }

    protected function findOr404(int $id): AssetAudit
    {
        try {
            return $this->audits->find(Auth::user(), $id);
        } catch (ActionFailed) {
            abort(404);
        }
    }
}
