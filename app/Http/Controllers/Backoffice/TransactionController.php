<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Services\Reports\SpreadsheetExporter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Transaksi pembayaran semua pelanggan (langganan, upgrade, add-on, storage).
 * Khusus Super Admin backoffice; tidak tersedia di aplikasi mobile.
 */
class TransactionController extends Controller
{
    public const PAID = ['success', 'settlement', 'capture', 'paid'];
    public const FAILED = ['deny', 'cancel', 'expire', 'expired', 'failure', 'failed'];

    public const STATUSES = [
        'paid' => 'Lunas',
        'pending' => 'Menunggu bayar',
        'failed' => 'Gagal / batal',
    ];

    public const TYPES = [
        'subscription' => 'Langganan',
        'upgrade' => 'Upgrade paket',
        'addon' => 'Add-on',
        'storage_addon' => 'Tambah storage',
    ];

    public const PERIODS = [
        'this_month' => 'Bulan ini',
        'last_month' => 'Bulan lalu',
        'last_30' => '30 hari terakhir',
        'this_year' => 'Tahun ini',
        'all' => 'Semua waktu',
    ];

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        [$from, $to] = $this->range($filters['period']);

        // Ringkasan periode (tanpa filter status agar semua angka terlihat).
        $base = $this->query($filters, $from, $to, withStatus: false);

        $summary = [
            'revenue' => (float) (clone $base)->whereIn('transaction_status', self::PAID)->sum('gross_amount'),
            'paid' => (clone $base)->whereIn('transaction_status', self::PAID)->count(),
            'pending' => (clone $base)->where('transaction_status', 'pending')->count(),
            'pending_amount' => (float) (clone $base)->where('transaction_status', 'pending')->sum('gross_amount'),
            'failed' => (clone $base)->whereIn('transaction_status', self::FAILED)->count(),
            'customers' => (clone $base)->whereIn('transaction_status', self::PAID)->distinct()->count('company_id'),
        ];

        $byType = (clone $base)->whereIn('transaction_status', self::PAID)
            ->selectRaw("COALESCE(purchase_type, 'subscription') as type, COUNT(*) as total, SUM(gross_amount) as amount")
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $transactions = $this->query($filters, $from, $to)
            ->with(['company:id,company_name,company_code', 'creator:id,name,email', 'plan:id,plan_name'])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('backoffice.transactions.index', [
            'transactions' => $transactions,
            'summary' => $summary,
            'byType' => $byType,
            'monthly' => $this->monthly(),
            'filters' => $filters,
            'from' => $from,
            'to' => $to,
            'statuses' => self::STATUSES,
            'types' => self::TYPES,
            'periods' => self::PERIODS,
        ]);
    }

    /**
     * Invoice sama persis dengan yang dilihat pelanggan.
     */
    public function invoice(PaymentTransaction $transaction): View
    {
        $transaction->load(['company', 'subscription.plan', 'plan']);

        // View invoice dashboard kini dua bahasa; backoffice tetap Bahasa Indonesia.
        \Illuminate\Support\Facades\App::setLocale('id');

        return view('dashboard.subscription.billing-print', [
            'payment' => $transaction,
            'company' => $transaction->company,
            'subscription' => $transaction->subscription,
            'plan' => $transaction->plan ?? $transaction->subscription?->plan,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        [$from, $to] = $this->range($filters['period']);

        $rows = $this->query($filters, $from, $to)
            ->with(['company:id,company_name,company_code', 'creator:id,name,email'])
            ->latest('id')
            ->limit(20000)
            ->get();

        $admin = auth('backoffice')->user();
        $excel = new SpreadsheetExporter('Vasetra Backoffice', $admin?->name);

        $excel->table('Transaksi', 'Transaksi Pelanggan Vasetra', [
            ['label' => 'Tanggal', 'type' => 'datetime', 'width' => 18, 'value' => fn ($t) => $t->created_at],
            ['key' => 'order_id', 'label' => 'No. order', 'width' => 26],
            ['label' => 'Perusahaan', 'width' => 30, 'value' => fn ($t) => $t->company?->company_name],
            ['label' => 'Kode PT', 'width' => 16, 'value' => fn ($t) => $t->company?->company_code],
            ['label' => 'Dibuat oleh', 'width' => 24, 'value' => fn ($t) => $t->creator ? $t->creator->name . ' (' . $t->creator->email . ')' : ''],
            ['label' => 'Jenis', 'width' => 16, 'value' => fn ($t) => self::TYPES[$t->purchase_type ?? 'subscription'] ?? $t->purchase_type],
            ['label' => 'Paket', 'width' => 18, 'value' => fn ($t) => $t->plan_name ?? $t->plan?->plan_name],
            ['key' => 'billing_cycle', 'label' => 'Siklus', 'width' => 11],
            ['label' => 'Diskon', 'type' => 'money', 'width' => 14, 'value' => fn ($t) => (float) $t->discount_amount],
            ['label' => 'Total', 'type' => 'money', 'width' => 16, 'value' => fn ($t) => (float) $t->gross_amount],
            ['label' => 'Status', 'width' => 15, 'value' => fn ($t) => self::statusLabel($t->transaction_status)],
            ['label' => 'Metode', 'width' => 16, 'value' => fn ($t) => self::methodLabel($t)],
            ['label' => 'Dibayar', 'type' => 'datetime', 'width' => 18, 'value' => fn ($t) => $t->settlement_time],
        ], $rows, self::PERIODS[$filters['period']] . ($from ? ' (' . $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y') . ')' : '') . ' · ' . $rows->count() . ' transaksi');

        return $excel->download('Vasetra-Transaksi-' . now()->format('Ymd-His'));
    }

    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    public static function statusKey(?string $status): string
    {
        $status = strtolower((string) $status);

        return match (true) {
            in_array($status, self::PAID, true) => 'paid',
            in_array($status, self::FAILED, true) => 'failed',
            default => 'pending',
        };
    }

    public static function statusLabel(?string $status): string
    {
        return self::STATUSES[self::statusKey($status)];
    }

    public static function methodLabel(PaymentTransaction $t): string
    {
        $type = (string) $t->payment_type;

        if ($type === '') {
            return '';
        }

        $label = ucwords(str_replace('_', ' ', $type));

        return $t->bank ? $label . ' ' . strtoupper((string) $t->bank) : $label;
    }

    private function filters(Request $request): array
    {
        $period = (string) $request->query('period', 'this_month');
        $status = (string) $request->query('status', '');
        $type = (string) $request->query('type', '');

        return [
            'period' => array_key_exists($period, self::PERIODS) ? $period : 'this_month',
            'status' => array_key_exists($status, self::STATUSES) ? $status : '',
            'type' => array_key_exists($type, self::TYPES) ? $type : '',
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 100),
        ];
    }

    /**
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    private function range(string $period): array
    {
        $now = CarbonImmutable::now();

        return match ($period) {
            'last_month' => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth()],
            'last_30' => [$now->subDays(29)->startOfDay(), $now->endOfDay()],
            'this_year' => [$now->startOfYear(), $now->endOfDay()],
            'all' => [null, null],
            default => [$now->startOfMonth(), $now->endOfDay()],
        };
    }

    private function query(array $f, ?CarbonImmutable $from, ?CarbonImmutable $to, bool $withStatus = true): Builder
    {
        return PaymentTransaction::query()
            ->when($from, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->when($f['type'] !== '', function ($q) use ($f) {
                $f['type'] === 'subscription'
                    ? $q->where(fn ($w) => $w->where('purchase_type', 'subscription')->orWhereNull('purchase_type'))
                    : $q->where('purchase_type', $f['type']);
            })
            ->when($withStatus && $f['status'] !== '', function ($q) use ($f) {
                match ($f['status']) {
                    'paid' => $q->whereIn('transaction_status', self::PAID),
                    'failed' => $q->whereIn('transaction_status', self::FAILED),
                    default => $q->where('transaction_status', 'pending'),
                };
            })
            ->when($f['q'] !== '', function ($q) use ($f) {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $f['q']) . '%';
                $q->where(function ($w) use ($like) {
                    $w->where('order_id', 'like', $like)
                        ->orWhereHas('company', fn ($c) => $c->where('company_name', 'like', $like)->orWhere('company_code', 'like', $like))
                        ->orWhereHas('creator', fn ($u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like));
                });
            });
    }

    /**
     * Pendapatan lunas 12 bulan terakhir (untuk grafik batang).
     */
    private function monthly(): array
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths(11);

        $rows = PaymentTransaction::query()
            ->whereIn('transaction_status', self::PAID)
            ->where('created_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(gross_amount) as amount, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('amount', 'ym');

        $months = [];

        for ($i = 0; $i < 12; $i++) {
            $m = $start->addMonths($i);
            $months[] = [
                'label' => $m->locale('id')->translatedFormat('M y'),
                'amount' => (float) ($rows[$m->format('Y-m')] ?? 0),
            ];
        }

        return $months;
    }
}
