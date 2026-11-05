<?php

namespace App\Services\Dashboard;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Maintenance;
use App\Models\MaintenanceRequest;
use App\Models\User;
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

        $assetQuery = Asset::query()->where('company_id', $companyId);

        // Jadwal maintenance yang masih terbuka.
        $openMaintenance = Maintenance::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['scheduled', 'in_progress']);

        /*
        |--------------------------------------------------------------------------
        | KPI
        |--------------------------------------------------------------------------
        */

        $totalAssets = (clone $assetQuery)->count();

        // assets.status: 1 = aktif.
        $activeAssets = (clone $assetQuery)->where('status', 1)->count();
        $myAssets = (clone $assetQuery)->where('responsible_user_id', $user->id)->count();
        $unassignedAssets = (clone $assetQuery)->whereNull('responsible_user_id')->count();
        $assetValue = (float) (clone $assetQuery)->sum('purchase_price');

        $newThisMonth = (clone $assetQuery)->where('created_at', '>=', $today->copy()->startOfMonth())->count();
        $newLastMonth = (clone $assetQuery)
            ->whereBetween('created_at', [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->subMonthNoOverflow()->endOfMonth(),
            ])
            ->count();

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

        $start = $today->copy()->startOfMonth()->subMonthsNoOverflow(11);

        $months = collect(range(0, 11))->map(fn ($i) => $start->copy()->addMonthsNoOverflow($i));

        $assetsPerMonth = (clone $assetQuery)
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn ($a) => $a->created_at->format('Y-m'))
            ->map->count();

        $maintenancePerMonth = Maintenance::query()
            ->where('company_id', $companyId)
            ->whereDate('maintenance_date', '>=', $start)
            ->whereDate('maintenance_date', '<=', $today)
            ->get(['maintenance_date'])
            ->groupBy(fn ($m) => Carbon::parse($m->maintenance_date)->format('Y-m'))
            ->map->count();

        $running = (clone $assetQuery)->where('created_at', '<', $start)->count();

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

        $assetConditions = (clone $assetQuery)
            ->selectRaw('asset_condition, COUNT(*) as total')
            ->groupBy('asset_condition')
            ->pluck('total', 'asset_condition');

        $assetCategories = (clone $assetQuery)
            ->with('category:id,category_name')
            ->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->category?->category_name ?? 'Tanpa kategori',
                'total' => (int) $row->total,
            ]);

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
            ->latest()
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
