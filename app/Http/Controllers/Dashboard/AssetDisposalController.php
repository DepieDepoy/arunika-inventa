<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Services\Asset\AssetLifecycleService;
use App\Services\Reports\SpreadsheetExporter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Penghapusan aset (disposal): aset rusak & dimusnahkan, hilang, dijual,
 * dihibahkan, atau dikembalikan ke vendor. Nilai buku saat penghapusan dan
 * laba/rugi dicatat untuk keperluan keuangan.
 */
class AssetDisposalController extends Controller
{
    public const PERIODS = [
        'this_year' => 'Tahun ini',
        'last_year' => 'Tahun lalu',
        'this_month' => 'Bulan ini',
        'last_90' => '90 hari terakhir',
        'all' => 'Semua waktu',
    ];

    public function __construct(
        protected AssetLifecycleService $lifecycle
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | DAFTAR PENGHAPUSAN
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        [$from, $to] = $this->range($filters['period']);

        $base = $this->query($filters, $from, $to)->whereNull('asset_disposals.cancelled_at');

        $summary = [
            'count' => (clone $base)->count(),
            'purchase' => (float) (clone $base)->sum('asset_disposals.purchase_price'),
            'book' => (float) (clone $base)->sum('asset_disposals.book_value'),
            'proceeds' => (float) (clone $base)->sum('asset_disposals.proceeds'),
            'gain_loss' => (float) (clone $base)->sum('asset_disposals.gain_loss'),
        ];

        $byType = (clone $base)
            ->selectRaw('asset_disposals.disposal_type as type, COUNT(*) as total, COALESCE(SUM(asset_disposals.book_value), 0) as book')
            ->groupBy('asset_disposals.disposal_type')
            ->get()
            ->keyBy('type');

        $disposals = $this->query($filters, $from, $to)
            ->when(!$filters['show_cancelled'], fn ($q) => $q->whereNull('asset_disposals.cancelled_at'))
            ->with(['asset:id,asset_code,asset_name,category_id,location', 'asset.category:id,category_name', 'creator:id,name', 'holder:id,name', 'canceller:id,name'])
            ->orderByDesc('asset_disposals.disposal_date')
            ->orderByDesc('asset_disposals.id')
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.asset.disposals.index', [
            'disposals' => $disposals,
            'summary' => $summary,
            'byType' => $byType,
            'filters' => $filters,
            'from' => $from,
            'to' => $to,
            'types' => AssetDisposal::TYPES,
            'periods' => self::PERIODS,
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request);
        [$from, $to] = $this->range($filters['period']);

        $rows = $this->query($filters, $from, $to)
            ->when(!$filters['show_cancelled'], fn ($q) => $q->whereNull('asset_disposals.cancelled_at'))
            ->with(['asset:id,asset_code,asset_name,category_id', 'asset.category:id,category_name', 'creator:id,name', 'holder:id,name'])
            ->orderByDesc('asset_disposals.disposal_date')
            ->limit(20000)
            ->get();

        $excel = SpreadsheetExporter::forCurrentUser();
        $excel->table('Penghapusan', 'Penghapusan Aset', [
            ['label' => 'Tanggal', 'type' => 'date', 'width' => 12, 'value' => fn ($d) => $d->disposal_date],
            ['label' => 'Kode aset', 'width' => 16, 'value' => fn ($d) => $d->asset?->asset_code],
            ['label' => 'Nama aset', 'width' => 30, 'value' => fn ($d) => $d->asset?->asset_name],
            ['label' => 'Kategori', 'width' => 18, 'value' => fn ($d) => $d->asset?->category?->category_name],
            ['label' => 'Alasan', 'width' => 22, 'value' => fn ($d) => $d->typeLabel()],
            ['label' => 'Harga perolehan', 'type' => 'money', 'width' => 16, 'value' => fn ($d) => $d->purchase_price !== null ? (float) $d->purchase_price : null],
            ['label' => 'Nilai buku', 'type' => 'money', 'width' => 16, 'value' => fn ($d) => $d->book_value !== null ? (float) $d->book_value : null],
            ['label' => 'Hasil penjualan / ganti', 'type' => 'money', 'width' => 18, 'value' => fn ($d) => (float) $d->proceeds],
            ['label' => 'Laba (rugi)', 'type' => 'money', 'width' => 16, 'value' => fn ($d) => $d->gain_loss !== null ? (float) $d->gain_loss : null],
            ['label' => 'Pemegang terakhir', 'width' => 20, 'value' => fn ($d) => $d->holder?->name],
            ['label' => 'Lokasi terakhir', 'width' => 20, 'value' => fn ($d) => $d->previous_location],
            ['label' => 'Catatan', 'width' => 36, 'value' => fn ($d) => $d->notes],
            ['label' => 'Dicatat oleh', 'width' => 18, 'value' => fn ($d) => $d->creator?->name],
            ['label' => 'Status', 'width' => 14, 'value' => fn ($d) => $d->isCancelled() ? 'Dibatalkan' : 'Berlaku'],
        ], $rows, self::PERIODS[$filters['period']] . ($from ? ' (' . $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y') . ')' : '') . ' - ' . $rows->count() . ' aset');

        return $excel->download('Penghapusan-Aset-' . now()->format('Ymd-His'));
    }

    /*
    |--------------------------------------------------------------------------
    | HAPUSKAN ASET
    |--------------------------------------------------------------------------
    */

    /** Pratinjau nilai buku & laba/rugi untuk tanggal dan hasil penjualan tertentu. */
    public function preview(Request $request, string $id): JsonResponse
    {
        $asset = $this->asset($id);

        $validated = $request->validate([
            'disposal_date' => ['required', 'date'],
            'proceeds' => ['nullable', 'numeric', 'min:0'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->lifecycle->valuation($asset, $validated['disposal_date'], (float) ($validated['proceeds'] ?? 0)),
        ]);
    }

    public function store(Request $request, string $id): RedirectResponse
    {
        $asset = $this->asset($id);

        $validated = $request->validate([
            'disposal_type' => ['required', Rule::in(array_keys(AssetDisposal::TYPES))],
            'disposal_date' => ['required', 'date'],
            'proceeds' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'document' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
            'confirm' => ['accepted'],
        ], [
            'disposal_type.required' => 'Alasan penghapusan wajib dipilih.',
            'disposal_date.required' => 'Tanggal penghapusan wajib diisi.',
            'document.mimes' => 'Berita acara harus berupa PDF, JPG, atau PNG.',
            'document.max' => 'Ukuran berita acara maksimal 10 MB.',
            'confirm.accepted' => 'Centang konfirmasi bahwa data penghapusan sudah benar.',
        ]);

        try {
            $disposal = $this->lifecycle->dispose($asset, $validated, $request->file('document'), Auth::user());
        } catch (ValidationException $e) {
            return back()->withInput()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()
            ->route('assets.show', $id)
            ->with('success', 'Aset berhasil dihapuskan (' . strtolower($disposal->typeLabel()) . '). Riwayatnya tetap tersimpan.');
    }

    public function cancel(Request $request, int $disposal): RedirectResponse
    {
        $disposal = AssetDisposal::query()
            ->where('company_id', Auth::user()->company_id)
            ->findOrFail($disposal);

        $validated = $request->validate([
            'cancel_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'cancel_reason.required' => 'Alasan pembatalan wajib diisi.',
            'cancel_reason.min' => 'Alasan pembatalan minimal 5 karakter.',
        ]);

        try {
            $asset = $this->lifecycle->cancelDisposal($disposal, $validated['cancel_reason'], Auth::user());
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()
            ->route('assets.show', encryptId($asset->id))
            ->with('success', 'Penghapusan dibatalkan. Aset kembali ' . strtolower($asset->statusLabel()) . '; serahkan ulang ke pemegang & atur jadwal maintenance bila perlu.');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER
    |--------------------------------------------------------------------------
    */

    protected function asset(string $id): Asset
    {
        return Asset::query()
            ->where('company_id', Auth::user()->company_id)
            ->findOrFail(decryptId($id));
    }

    protected function filters(Request $request): array
    {
        $period = (string) $request->query('period', 'this_year');
        $type = (string) $request->query('type', '');

        return [
            'period' => array_key_exists($period, self::PERIODS) ? $period : 'this_year',
            'type' => array_key_exists($type, AssetDisposal::TYPES) ? $type : '',
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 100),
            'show_cancelled' => $request->boolean('show_cancelled'),
        ];
    }

    /**
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    protected function range(string $period): array
    {
        $now = CarbonImmutable::now();

        return match ($period) {
            'last_year' => [$now->subYear()->startOfYear(), $now->subYear()->endOfYear()],
            'this_month' => [$now->startOfMonth(), $now->endOfDay()],
            'last_90' => [$now->subDays(89)->startOfDay(), $now->endOfDay()],
            'all' => [null, null],
            default => [$now->startOfYear(), $now->endOfDay()],
        };
    }

    protected function query(array $f, ?CarbonImmutable $from, ?CarbonImmutable $to): Builder
    {
        return AssetDisposal::query()
            ->where('asset_disposals.company_id', Auth::user()->company_id)
            ->when($from, fn ($q) => $q->whereBetween('asset_disposals.disposal_date', [$from->toDateString(), $to->toDateString()]))
            ->when($f['type'] !== '', fn ($q) => $q->where('asset_disposals.disposal_type', $f['type']))
            ->when($f['q'] !== '', function ($q) use ($f) {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $f['q']) . '%';
                $q->whereHas('asset', fn ($a) => $a->withTrashed()->where(fn ($w) => $w
                    ->where('asset_code', 'like', $like)
                    ->orWhere('asset_name', 'like', $like)));
            });
    }
}
