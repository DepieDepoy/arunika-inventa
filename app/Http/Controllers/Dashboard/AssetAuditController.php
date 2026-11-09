<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AssetAudit;
use App\Models\AssetAuditItem;
use App\Services\Asset\AssetAuditService;
use App\Services\Asset\AssetLifecycleService;
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
        protected AssetAuditService $audits,
        protected AssetLifecycleService $lifecycle
    ) {
    }

    public function index(): View
    {
        $user = Auth::user();
        $companyId = (int) $user->company_id;

        $audits = $this->audits->visibleQuery($user)
            ->with(['branch', 'starter'])
            ->orderByRaw("status = 'in_progress' DESC")
            ->latest('id')
            ->paginate(15);

        $audits->getCollection()->each(fn (AssetAudit $a) => $a->setAttribute('stats', $a->summary()));

        return view('dashboard.asset-audit.index', [
            'audits' => $audits,
            'branches' => BranchAccess::options($companyId, $user),
            'restricted' => BranchAccess::restrictedBranchId($user),
            'multiBranch' => BranchAccess::hasMultiple($companyId),
            'locations' => $this->lifecycle->knownLocations($companyId),
        ]);
    }

    public function store(Request $request): RedirectResponse
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
            $audit = $this->audits->create(Auth::user(), $validated['title'], $validated['branch_id'] ?? null, $validated['location_filter'] ?? null, $validated['notes'] ?? null);
        } catch (ActionFailed $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('asset-audits.show', $audit->id)
            ->with('success', 'Audit ' . $audit->audit_no . ' dimulai dengan ' . $audit->items()->count() . ' aset. Silakan mulai scan QR aset.');
    }

    public function show(Request $request, int $audit): View
    {
        $audit = $this->findOr404($audit);
        $audit->load(['branch', 'starter', 'completer']);

        $filter = in_array($request->get('f'), AssetAuditService::FILTERS, true) ? $request->get('f') : 'all';

        $items = $this->audits->itemsQuery($audit, $filter, $request->get('q'))
            ->with('checker:id,name')
            ->paginate(50)
            ->withQueryString();

        $user = Auth::user();

        return view('dashboard.asset-audit.show', [
            'audit' => $audit,
            'items' => $items,
            'stats' => $audit->summary(),
            'filter' => $filter,
            'canScan' => $audit->isOpen() && $user->hasPermission('asset_audit.scan'),
            'canManage' => $audit->isOpen() && $user->hasPermission('asset_audit.create'),
            'locations' => $this->lifecycle->knownLocations((int) $audit->company_id),
        ]);
    }

    public function scan(Request $request, int $audit): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
            'condition' => ['nullable', 'in:' . implode(',', array_keys(AssetAuditItem::CONDITIONS))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $model = $this->audits->find(Auth::user(), $audit);
            $result = $this->audits->scan(Auth::user(), $model, $validated['code'], $validated['location'] ?? null, $validated['condition'] ?? null, $validated['notes'] ?? null);
        } catch (ActionFailed $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status);
        }

        $item = $this->audits->itemPayload($result['item']);
        // Tampilan web memakai jam saja.
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

    public function complete(Request $request, int $audit): RedirectResponse
    {
        try {
            $model = $this->audits->find(Auth::user(), $audit);
            $result = $this->audits->complete(Auth::user(), $model, $request->boolean('apply_locations'));
        } catch (ActionFailed $e) {
            return back()->with('error', $e->getMessage());
        }

        $s = $result['summary'];

        return redirect()->route('asset-audits.show', $model->id)->with(
            'success',
            "Audit selesai: {$s['found']} ditemukan, {$s['missing']} tidak ditemukan, {$s['misplaced']} salah lokasi."
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
            ->with('checker:id,name')
            ->orderByRaw("FIELD(result, 'missing', 'found')")
            ->orderBy('asset_code')
            ->get();

        $stateLabels = ['pending' => 'Belum dicek', 'found' => 'Ditemukan', 'misplaced' => 'Salah lokasi', 'missing' => 'Tidak ditemukan', 'extra' => 'Di luar daftar'];
        $s = $audit->summary();

        $excel = SpreadsheetExporter::forCurrentUser();
        $excel->table('Audit Fisik', 'Audit Fisik Aset ' . $audit->audit_no . ' — ' . $audit->title, [
            ['key' => 'asset_code', 'label' => 'Kode aset', 'width' => 16],
            ['key' => 'asset_name', 'label' => 'Nama aset', 'width' => 30],
            ['label' => 'Hasil', 'width' => 16, 'value' => fn (AssetAuditItem $i) => $stateLabels[$i->state()] ?? $i->state()],
            ['key' => 'expected_location', 'label' => 'Lokasi tercatat', 'width' => 22],
            ['key' => 'found_location', 'label' => 'Lokasi ditemukan', 'width' => 22],
            ['label' => 'Kondisi', 'width' => 14, 'value' => fn (AssetAuditItem $i) => $i->conditionLabel() ?? ''],
            ['key' => 'notes', 'label' => 'Catatan', 'width' => 28],
            ['label' => 'Dicek oleh', 'width' => 18, 'value' => fn (AssetAuditItem $i) => $i->checker?->name ?? ''],
            ['label' => 'Waktu cek', 'width' => 18, 'value' => fn (AssetAuditItem $i) => $i->checked_at?->format('d/m/Y H:i') ?? ''],
        ], $items, sprintf(
            '%s · %s · %d aset: %d ditemukan, %d tidak ditemukan, %d salah lokasi, %d rusak, %d di luar daftar',
            $audit->branch?->branch_name ?? 'Semua cabang',
            $audit->statusLabel(),
            $s['total'], $s['found'], $s['missing'], $s['misplaced'], $s['damaged'], $s['extra']
        ));

        return $excel;
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
