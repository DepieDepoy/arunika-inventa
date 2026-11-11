<?php

namespace App\Services\Backoffice;

use App\Models\Company;
use App\Models\Subscription;
use App\Services\CompanyStorage;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Data pelanggan (perusahaan) untuk Backoffice Vasetra:
 * status langganan, pemakaian kuota, dan ringkasan bisnis.
 *
 * Semua filter, urutan, paging & agregat dikerjakan di database (server side)
 * agar tetap ringan walau jumlah perusahaan / user / aset sangat besar.
 * Hanya baris yang ditampilkan yang di-decorate() di PHP.
 */
class CustomerDirectory
{
    public const STATUS = [
        'trial' => 'Trial',
        'paid' => 'Berbayar',
        'expired' => 'Kedaluwarsa',
        'suspended' => 'Ditangguhkan',
    ];

    public const STATUS_ICON = [
        'trial' => 'bi-hourglass-split',
        'paid' => 'bi-patch-check',
        'expired' => 'bi-clock-history',
        'suspended' => 'bi-slash-circle',
    ];

    /** Sama dengan decorate(): status pelanggan dari status PT + langganan aktif. */
    public const STATUS_SQL = "CASE
        WHEN companies.status IS NULL OR companies.status <> 1 THEN 'suspended'
        WHEN asub.id IS NULL THEN 'expired'
        WHEN asub.billing_cycle = 'trial' OR UPPER(ap.plan_code) = 'FREE' THEN 'trial'
        ELSE 'paid' END";

    /** Batas storage = max(total storage langganan, storage paket). */
    public const STORAGE_LIMIT_SQL = 'CASE WHEN asub.id IS NULL THEN 0
        ELSE GREATEST(COALESCE(asub.total_storage_bytes, 0), COALESCE(ap.max_storage_bytes, 0)) END';

    /** Nilai langganan per bulan (lihat monthlyValue()). */
    public const MRR_SQL = 'asub.total_price / GREATEST(1, ROUND(DATEDIFF(asub.end_date, asub.start_date) / 30))';

    public function __construct(protected SubscriptionService $subscriptions)
    {
    }

    /**
     * Query perusahaan + langganan aktif (asub) + paketnya (ap).
     * Langganan aktif mengikuti Company::activeSubscription():
     * status active, berlaku hari ini, priority lalu id terbesar.
     */
    public function query(): Builder
    {
        $today = today()->toDateString();

        $activeSub = DB::table('subscriptions as s')
            ->select('s.id')
            ->whereColumn('s.company_id', 'companies.id')
            ->where('s.status', 'active')
            ->whereDate('s.start_date', '<=', $today)
            ->whereDate('s.end_date', '>=', $today)
            ->orderByDesc('s.priority')
            ->orderByDesc('s.id')
            ->limit(1);

        $base = DB::table('companies')->select('companies.*')->selectSub($activeSub, 'active_sub_id');

        return Company::query()
            ->fromSub($base, 'companies')
            ->leftJoin('subscriptions as asub', 'asub.id', '=', 'companies.active_sub_id')
            ->leftJoin('plans as ap', 'ap.id', '=', 'asub.plan_id')
            ->select('companies.*');
    }

    /** Jumlah user/aset & login terakhir (subquery per baris). */
    public function withCounts(Builder $query): Builder
    {
        return $query
            ->selectSub(DB::table('users')->selectRaw('count(*)')->whereColumn('users.company_id', 'companies.id'), 'users_count')
            ->selectSub(DB::table('assets')->selectRaw('count(*)')->whereColumn('assets.company_id', 'companies.id'), 'assets_count')
            ->selectSub(DB::table('users')->selectRaw('max(last_login_at)')->whereColumn('users.company_id', 'companies.id'), 'last_activity_at');
    }

    public function whereStatus(Builder $query, string|array $status): Builder
    {
        $status = (array) $status;

        return $query->whereRaw('(' . self::STATUS_SQL . ') IN (' . implode(',', array_fill(0, count($status), '?')) . ')', $status);
    }

    /**
     * Pencarian: nama/kode PT, atau email / no. HP / nama user di PT itu.
     */
    public function search(Builder $query, string $q): Builder
    {
        $like = '%' . addcslashes($q, '%_\\') . '%';

        return $query->where(function ($w) use ($like) {
            $w->where('companies.company_name', 'like', $like)
                ->orWhere('companies.company_code', 'like', $like)
                ->orWhereExists(function ($u) use ($like) {
                    $u->selectRaw('1')
                        ->from('users')
                        ->whereColumn('users.company_id', 'companies.id')
                        ->where(fn ($x) => $x->where('users.email', 'like', $like)
                            ->orWhere('users.phone', 'like', $like)
                            ->orWhere('users.name', 'like', $like));
                });
        });
    }

    public function sort(Builder $query, string $sort): Builder
    {
        match ($sort) {
            'assets' => $query->orderByDesc('assets_count'),
            'users' => $query->orderByDesc('users_count'),
            'storage' => $query->orderByDesc('companies.storage_used_bytes'),
            'ending' => $query->orderByRaw('asub.end_date IS NULL')->orderBy('asub.end_date'),
            'activity' => $query->orderByDesc('last_activity_at'),
            default => $query->orderByDesc('companies.created_at'),
        };

        return $query->orderByDesc('companies.id');
    }

    /** Jumlah perusahaan per status (satu query agregat). */
    public function statusCounts(): Collection
    {
        return $this->query()
            ->toBase()
            ->reorder()
            ->select(DB::raw('(' . self::STATUS_SQL . ') AS st, COUNT(*) AS total'))
            ->groupBy('st')
            ->pluck('total', 'st')
            ->map(fn ($v) => (int) $v);
    }

    /**
     * Lengkapi baris yang ditampilkan saja (maks. beberapa puluh) dengan atribut turunan.
     *
     * @param  iterable<Company>  $companies
     */
    public function decorateMany(iterable $companies): Collection
    {
        $companies = Collection::wrap($companies);

        if ($companies->isNotEmpty()) {
            (new \Illuminate\Database\Eloquent\Collection($companies->all()))->load('activeSubscription.plan');
        }

        return $companies->each(fn (Company $c) => $this->decorate($c));
    }

    /**
     * Tambahkan atribut turunan: status pelanggan, kuota, storage.
     */
    public function decorate(Company $company): Company
    {
        $sub = $company->activeSubscription;
        $plan = $sub?->plan;

        $company->customer_status = match (true) {
            (int) $company->status !== 1 => 'suspended',
            !$sub => 'expired',
            $this->subscriptions->isTrial($sub) => 'trial',
            default => 'paid',
        };

        $company->plan_label = $plan?->plan_name ?? '—';
        $company->ends_at = $sub?->end_date;
        $company->days_left = $sub ? (int) today()->diffInDays($sub->end_date, false) : null;
        $company->max_users = $plan?->max_users;
        $company->max_assets = $plan?->max_assets;
        $company->storage_limit = $sub ? max((int) $sub->total_storage_bytes, (int) ($plan?->max_storage_bytes ?? 0)) : 0;
        $company->storage_used = $this->storageUsed($company->id);
        $company->mrr = $company->customer_status === 'paid' ? $this->monthlyValue($sub) : 0.0;

        return $company;
    }

    /**
     * Storage terpakai (scan folder) di-cache 15 menit agar daftar tetap cepat.
     * Hasil scan juga disimpan ke companies.storage_used_bytes untuk urutan & total
     * (diperbarui berkala oleh perintah backoffice:scan-storage).
     */
    public function storageUsed(int $companyId, bool $fresh = false): int
    {
        $key = "backoffice_storage_used_{$companyId}";

        if ($fresh) {
            Cache::forget($key);
        }

        return (int) Cache::remember($key, now()->addMinutes(15), function () use ($companyId) {
            $bytes = CompanyStorage::usedBytes($companyId);

            DB::table('companies')->where('id', $companyId)->update([
                'storage_used_bytes' => $bytes,
                'storage_scanned_at' => now(),
            ]);

            return $bytes;
        });
    }

    /**
     * Nilai langganan per bulan (estimasi MRR).
     */
    public function monthlyValue(?Subscription $sub): float
    {
        if (!$sub) {
            return 0.0;
        }

        $months = max(1, (int) round($sub->start_date->diffInDays($sub->end_date) / 30));

        return round((float) $sub->total_price / $months, 2);
    }

    /**
     * Ringkasan bisnis untuk dashboard backoffice (semua agregat di SQL).
     */
    public function summary(): array
    {
        $byStatus = $this->statusCounts();
        $startMonth = now()->startOfMonth();
        $prevMonth = now()->subMonthNoOverflow()->startOfMonth();

        $mrr = (float) $this->whereStatus($this->query(), 'paid')
            ->toBase()
            ->reorder()
            ->select(DB::raw('COALESCE(SUM(' . self::MRR_SQL . '), 0) AS total_mrr'))
            ->first()
            ?->total_mrr;

        return [
            'companies' => (int) $byStatus->sum(),
            'trial' => $byStatus['trial'] ?? 0,
            'paid' => $byStatus['paid'] ?? 0,
            'expired' => $byStatus['expired'] ?? 0,
            'suspended' => $byStatus['suspended'] ?? 0,
            'new_this_month' => Company::query()->where('created_at', '>=', $startMonth)->count(),
            'new_last_month' => Company::query()->whereBetween('created_at', [$prevMonth, $startMonth->copy()->subSecond()])->count(),
            'mrr' => round($mrr, 2),
            'users' => DB::table('users')->whereNotNull('company_id')->count(),
            'assets' => DB::table('assets')->count(),
            'storage' => (int) DB::table('companies')->sum('storage_used_bytes'),
            'active_7d' => DB::table('users')
                ->whereNotNull('company_id')
                ->where('last_login_at', '>=', now()->subDays(7))
                ->distinct()
                ->count('company_id'),
            'paid_revenue_month' => (float) DB::table('payment_transactions')
                ->whereIn('transaction_status', ['settlement', 'capture', 'paid', 'success'])
                ->where('created_at', '>=', $startMonth)
                ->sum('gross_amount'),
        ];
    }

    /**
     * Jumlah perusahaan baru per bulan (12 bulan terakhir).
     */
    public function monthlySignups(int $months = 12): array
    {
        $start = now()->startOfMonth()->subMonthsNoOverflow($months - 1);

        $counts = DB::table('companies')
            ->where('created_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $rows = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonthsNoOverflow($i);
            $rows[] = [
                'label' => $month->locale('id')->translatedFormat('M y'),
                'total' => (int) ($counts[$month->format('Y-m')] ?? 0),
            ];
        }

        return $rows;
    }

    /** Komposisi paket perusahaan trial & berbayar. */
    public function planMix(): array
    {
        return $this->whereStatus($this->query(), ['trial', 'paid'])
            ->toBase()
            ->reorder()
            ->select(DB::raw("COALESCE(ap.plan_name, '—') AS label, COUNT(*) AS total"))
            ->groupBy('label')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['label' => (string) $r->label, 'total' => (int) $r->total])
            ->all();
    }

    /** Langganan trial / berbayar yang berakhir ≤ $days hari lagi. */
    public function expiring(int $days = 7, int $limit = 8): Collection
    {
        $rows = $this->whereStatus($this->query(), ['trial', 'paid'])
            ->whereDate('asub.end_date', '<=', today()->addDays($days))
            ->orderBy('asub.end_date')
            ->orderByDesc('companies.id')
            ->limit($limit)
            ->get();

        return $this->decorateMany($rows);
    }

    public function recent(int $limit = 6): Collection
    {
        return $this->decorateMany(
            $this->withCounts($this->query())->orderByDesc('companies.created_at')->orderByDesc('companies.id')->limit($limit)->get()
        );
    }

    public function topAssets(int $limit = 6): Collection
    {
        return $this->decorateMany(
            $this->withCounts($this->query())->orderByDesc('assets_count')->orderByDesc('companies.id')->limit($limit)->get()
        );
    }

    /** Storage terpakai ≥ 80% (berdasarkan hasil scan terakhir). */
    public function storageAlerts(float $ratio = 0.8, int $limit = 6): Collection
    {
        $limitSql = '(' . self::STORAGE_LIMIT_SQL . ')';

        $rows = $this->query()
            ->whereRaw("$limitSql > 0")
            ->whereRaw("companies.storage_used_bytes >= $limitSql * ?", [$ratio])
            ->orderByRaw("companies.storage_used_bytes / $limitSql DESC")
            ->limit($limit)
            ->get();

        return $this->decorateMany($rows);
    }

    /** Satu perusahaan lengkap dengan hitungan & atribut turunan. */
    public function find(int $companyId): ?Company
    {
        $company = $this->withCounts($this->query())->where('companies.id', $companyId)->first();

        return $company ? $this->decorate($company->load('activeSubscription.plan')) : null;
    }
}
