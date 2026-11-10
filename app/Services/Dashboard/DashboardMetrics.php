<?php

namespace App\Services\Dashboard;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Maintenance;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\Asset\AssetStats;
use App\Services\Subscription\SubscriptionService;
use Carbon\Carbon;

/**
 * Angka-angka dashboard yang dipakai bersama oleh web (HomeController)
 * dan API mobile (Api\V1\DashboardController), supaya keduanya selalu sama.
 */
class DashboardMetrics
{
    public function __construct(
        protected SubscriptionService $subscriptions
    ) {
    }

    public function build(User $user): array
    {
        $companyId = (int) $user->company_id;
        $today = today();

        // Aset dihapuskan (status 2) tidak lagi dihitung sebagai aset perusahaan.
        $assetQuery = Asset::query()->where('company_id', $companyId)->inService();

        // Jadwal maintenance yang masih terbuka.
        $openMaintenance = Maintenance::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['scheduled', 'in_progress']);

        /*
        |--------------------------------------------------------------------------
        | KPI
        |--------------------------------------------------------------------------
        */

        /*
         * Semua KPI aset dihitung dalam SATU kali pindai tabel (bukan banyak
         * COUNT terpisah) agar tetap ringan walau aset mencapai jutaan.
         * assets.status: 1 = aktif.
         */
        $start = $today->copy()->startOfMonth()->subMonthsNoOverflow(11);
        $thisMonth = $today->copy()->startOfMonth();
        $lastMonthStart = $today->copy()->subMonthNoOverflow()->startOfMonth();

        // Di-cache per perusahaan + cakupan cabang; segar otomatis saat aset berubah (AssetStats).
        $kpi = (object) AssetStats::remember($companyId, 'dashboard-kpi:' . $today->toDateString(), fn () => (array) (clone $assetQuery)
            ->toBase()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(status = 1) AS active')
            ->selectRaw('SUM(responsible_user_id IS NULL) AS unassigned')
            ->selectRaw('COALESCE(SUM(purchase_price), 0) AS value')
            ->selectRaw('SUM(created_at >= ?) AS new_this_month', [$thisMonth])
            ->selectRaw('SUM(created_at >= ? AND created_at < ?) AS new_last_month', [$lastMonthStart, $thisMonth])
            ->selectRaw('SUM(created_at < ?) AS before_trend', [$start])
            ->first());

        $totalAssets = (int) $kpi->total;
        $activeAssets = (int) $kpi->active;
        // Aset yang dipegang user ini: cepat lewat index pemegang aset.
        $myAssets = (clone $assetQuery)->where('responsible_user_id', $user->id)->count();
        $unassignedAssets = (int) $kpi->unassigned;
        $assetValue = (float) $kpi->value;
        $newThisMonth = (int) $kpi->new_this_month;
        $newLastMonth = (int) $kpi->new_last_month;

        $maintenanceOverdue = (clone $openMaintenance)->whereDate('maintenance_date', '<', $today)->count();
        $maintenanceToday = (clone $openMaintenance)->whereDate('maintenance_date', $today)->count();
        $maintenanceThisWeek = (clone $openMaintenance)
            ->whereDate('maintenance_date', '>', $today)
            ->whereDate('maintenance_date', '<=', $today->copy()->endOfWeek())
            ->count();
        $maintenanceOpen = (clone $openMaintenance)->count();

        $requestQuery = MaintenanceRequest::query()->where('company_id', $companyId);
        $requestPending = (clone $requestQuery)->where('status', 'pending')->count();
        $requestInProgress = (clone $requestQuery)->where('status', 'in_progress')->count();

        $maintenanceCostYear = (float) Maintenance::query()
            ->where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereYear('maintenance_date', $today->year)
            ->sum('cost');

        /*
        |--------------------------------------------------------------------------
        | TREN 12 BULAN: aset baru & maintenance selesai per bulan
        |--------------------------------------------------------------------------
        */

        $months = collect(range(0, 11))->map(fn ($i) => $start->copy()->addMonthsNoOverflow($i));

        // Dikelompokkan di database (bukan memuat semua baris ke PHP).
        $assetsPerMonth = collect(AssetStats::remember($companyId, 'dashboard-trend:' . $today->toDateString(), fn () => (clone $assetQuery)
            ->toBase()
            ->where('created_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c")
            ->groupBy('ym')
            ->pluck('c', 'ym')
            ->all()));

        $maintenancePerMonth = Maintenance::query()
            ->where('company_id', $companyId)
            ->whereDate('maintenance_date', '>=', $start)
            ->whereDate('maintenance_date', '<=', $today)
            ->toBase()
            ->selectRaw("DATE_FORMAT(maintenance_date, '%Y-%m') AS ym, COUNT(*) AS c")
            ->groupBy('ym')
            ->pluck('c', 'ym');

        $running = (int) $kpi->before_trend;

        $trend = $months->map(function ($month) use ($assetsPerMonth, $maintenancePerMonth, &$running) {
            $key = $month->format('Y-m');
            $new = (int) ($assetsPerMonth[$key] ?? 0);
            $running += $new;

            return [
                'key' => $key,
                'label' => $month->translatedFormat('M y'),
                'new_assets' => $new,
                'total_assets' => $running,
                'maintenance' => (int) ($maintenancePerMonth[$key] ?? 0),
            ];
        })->values();

        /*
        |--------------------------------------------------------------------------
        | KOMPOSISI
        |--------------------------------------------------------------------------
        */

        $assetConditions = collect(AssetStats::remember($companyId, 'dashboard-conditions', fn () => (clone $assetQuery)
            ->toBase()
            ->selectRaw('asset_condition, COUNT(*) as total')
            ->groupBy('asset_condition')
            ->pluck('total', 'asset_condition')
            ->all()));

        $assetCategories = collect(AssetStats::remember($companyId, 'dashboard-categories', fn () => (clone $assetQuery)
            ->with('category:id,category_name')
            ->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->category?->category_name ?? 'Tanpa kategori',
                'total' => (int) $row->total,
            ])
            ->all()));

        /*
        |--------------------------------------------------------------------------
        | DAFTAR
        |--------------------------------------------------------------------------
        */

        $upcomingMaintenance = (clone $openMaintenance)
            ->with(['asset.category', 'asset.responsibleUser'])
            ->orderBy('maintenance_date')
            ->limit(6)
            ->get();

        $recentAssets = (clone $assetQuery)
            ->with(['category', 'responsibleUser'])
            ->latest('id')
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | ADMINISTRATOR: kuota & aktivitas terbaru
        |--------------------------------------------------------------------------
        */

        $isAdministrator = $user->isAdministrator();
        $quota = null;
        $quotaUsage = null;
        $recentActivity = collect();

        if ($isAdministrator) {
            $quota = $this->subscriptions->quota($companyId);
            $quotaUsage = $this->subscriptions->usage($companyId);

            $recentActivity = AuditLog::query()
                ->where('company_id', $companyId)
                ->latest('id')
                ->limit(7)
                ->get(['id', 'user_name', 'event', 'module', 'description', 'created_at']);
        }

        $currentSubscription = $this->subscriptions->current($companyId);

        return compact(
            'user',
            'isAdministrator',
            'currentSubscription',
            'totalAssets',
            'activeAssets',
            'myAssets',
            'unassignedAssets',
            'assetValue',
            'newThisMonth',
            'newLastMonth',
            'maintenanceOverdue',
            'maintenanceToday',
            'maintenanceThisWeek',
            'maintenanceOpen',
            'requestPending',
            'requestInProgress',
            'maintenanceCostYear',
            'trend',
            'assetConditions',
            'assetCategories',
            'upcomingMaintenance',
            'recentAssets',
            'quota',
            'quotaUsage',
            'recentActivity'
        );
    }
}
