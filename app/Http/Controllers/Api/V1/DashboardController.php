<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Audit\AuditLogger;
use App\Services\Dashboard\DashboardMetrics;
use App\Services\Mobile\AccountPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ringkasan beranda aplikasi mobile.
 * Angkanya sama persis dengan dashboard web (DashboardMetrics).
 */
class DashboardController extends ApiController
{
    public function index(Request $request, DashboardMetrics $metrics, AccountPresenter $presenter): JsonResponse
    {
        $user = $request->user();
        $m = $metrics->build($user);

        $data = [
            'kpi' => [
                'total_assets' => $m['totalAssets'],
                'active_assets' => $m['activeAssets'],
                'my_assets' => $m['myAssets'],
                'unassigned_assets' => $m['unassignedAssets'],
                'asset_value' => $m['assetValue'],
                'new_assets_this_month' => $m['newThisMonth'],
                'new_assets_last_month' => $m['newLastMonth'],
                'maintenance_overdue' => $m['maintenanceOverdue'],
                'maintenance_today' => $m['maintenanceToday'],
                'maintenance_this_week' => $m['maintenanceThisWeek'],
                'maintenance_open' => $m['maintenanceOpen'],
                'maintenance_cost_this_year' => $m['maintenanceCostYear'],
                'requests_pending' => $m['requestPending'],
                'requests_in_progress' => $m['requestInProgress'],
            ],

            // 12 bulan terakhir, urut dari terlama.
            'trend' => $m['trend'],

            'asset_conditions' => collect($m['assetConditions'])
                ->map(fn ($total, $condition) => [
                    'condition' => $condition ?: 'unknown',
                    'total' => (int) $total,
                ])
                ->values(),

            'asset_categories' => $m['assetCategories'],

            'upcoming_maintenance' => $m['upcomingMaintenance']->map(fn ($item) => [
                'id' => $item->id,
                'maintenance_code' => $item->maintenance_code,
                'maintenance_type' => $item->maintenance_type,
                'maintenance_date' => $this->date($item->maintenance_date),
                'status' => $item->status,
                'is_overdue' => $item->maintenance_date && $item->maintenance_date->lt(today()),
                'asset' => $item->asset ? [
                    'id' => $item->asset->id,
                    'asset_code' => $item->asset->asset_code,
                    'asset_name' => $item->asset->asset_name,
                    'category' => $item->asset->category?->category_name,
                    'responsible_user' => $item->asset->responsibleUser?->name,
                ] : null,
            ])->values(),

            'recent_assets' => $m['recentAssets']->map(fn ($asset) => [
                'id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'asset_name' => $asset->asset_name,
                'category' => $asset->category?->category_name,
                'responsible_user' => $asset->responsibleUser?->name,
                'created_at' => $this->dateTime($asset->created_at),
            ])->values(),

            'subscription' => $presenter->subscription($user),
            'unread_notifications' => $user->unreadNotifications()->count(),
        ];

        // Khusus administrator: kuota paket & aktivitas terbaru.
        if ($m['isAdministrator']) {
            $quota = $m['quota'];
            $usage = $m['quotaUsage'];

            $data['quota'] = collect(['users', 'assets', 'storage'])
                ->mapWithKeys(fn ($key) => [$key => [
                    'used' => (int) ($usage[$key] ?? 0),
                    'limit' => $quota[$key] ?? null, // null = tanpa batas
                    'exceeded' => isset($quota[$key]) && ($usage[$key] ?? 0) > $quota[$key],
                ]])
                ->all();

            $data['recent_activity'] = $m['recentActivity']->map(fn ($log) => [
                'id' => $log->id,
                'user_name' => $log->user_name,
                'event' => $log->event,
                'module' => $log->module,
                'module_label' => AuditLogger::moduleLabel($log->module),
                'description' => $log->description,
                'created_at' => $this->dateTime($log->created_at),
            ])->values();
        }

        return $this->ok($data);
    }
}
