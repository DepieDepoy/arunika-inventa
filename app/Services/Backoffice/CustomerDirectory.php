<?php

namespace App\Services\Backoffice;

use App\Models\Company;
use App\Models\Subscription;
use App\Services\CompanyStorage;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Data pelanggan (perusahaan) untuk Backoffice Vasetra:
 * status langganan, pemakaian kuota, dan ringkasan bisnis.
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

    public function __construct(protected SubscriptionService $subscriptions)
    {
    }

    /**
     * Semua perusahaan + paket berjalan + jumlah user/aset + login terakhir.
     *
     * @return Collection<int, Company>
     */
    public function all(): Collection
    {
        $companies = Company::query()
            ->select('companies.*')
            ->selectSub(DB::table('users')->selectRaw('count(*)')->whereColumn('users.company_id', 'companies.id'), 'users_count')
            ->selectSub(DB::table('assets')->selectRaw('count(*)')->whereColumn('assets.company_id', 'companies.id'), 'assets_count')
            ->selectSub(DB::table('users')->selectRaw('max(last_login_at)')->whereColumn('users.company_id', 'companies.id'), 'last_activity_at')
            ->with(['activeSubscription.plan'])
            ->orderByDesc('companies.created_at')
            ->get();

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
     */
    public function storageUsed(int $companyId, bool $fresh = false): int
    {
        $key = "backoffice_storage_used_{$companyId}";

        if ($fresh) {
            Cache::forget($key);
        }

        return (int) Cache::remember($key, now()->addMinutes(15), fn () => CompanyStorage::usedBytes($companyId));
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
     * Ringkasan bisnis untuk dashboard backoffice.
     */
    public function summary(Collection $companies): array
    {
        $byStatus = $companies->countBy('customer_status');
        $startMonth = now()->startOfMonth();
        $prevMonth = now()->subMonthNoOverflow()->startOfMonth();

        return [
            'companies' => $companies->count(),
            'trial' => $byStatus['trial'] ?? 0,
            'paid' => $byStatus['paid'] ?? 0,
            'expired' => $byStatus['expired'] ?? 0,
            'suspended' => $byStatus['suspended'] ?? 0,
            'new_this_month' => $companies->filter(fn ($c) => $c->created_at?->gte($startMonth))->count(),
            'new_last_month' => $companies->filter(fn ($c) => $c->created_at?->between($prevMonth, $startMonth->copy()->subSecond()))->count(),
            'mrr' => $companies->sum('mrr'),
            'users' => (int) $companies->sum('users_count'),
            'assets' => (int) $companies->sum('assets_count'),
            'storage' => (int) $companies->sum('storage_used'),
            'active_7d' => $companies->filter(fn ($c) => $c->last_activity_at && now()->subDays(7)->lte($c->last_activity_at))->count(),
            'paid_revenue_month' => (float) DB::table('payment_transactions')
                ->whereIn('transaction_status', ['settlement', 'capture', 'paid', 'success'])
                ->where('created_at', '>=', $startMonth)
                ->sum('gross_amount'),
        ];
    }

    /**
     * Jumlah perusahaan baru per bulan (12 bulan terakhir).
     */
    public function monthlySignups(Collection $companies, int $months = 12): array
    {
        $rows = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonthsNoOverflow($i);
            $rows[] = [
                'label' => $month->locale('id')->translatedFormat('M y'),
                'total' => $companies->filter(fn ($c) => $c->created_at && $c->created_at->isSameMonth($month))->count(),
            ];
        }

        return $rows;
    }
}
