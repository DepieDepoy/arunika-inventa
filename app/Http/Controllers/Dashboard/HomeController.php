<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Maintenance;
use App\Services\Dashboard\DashboardMetrics;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(DashboardMetrics $metrics): View
    {
        // Perhitungan dipakai bersama dengan API mobile (DashboardMetrics).
        return view('dashboard.home.index', $metrics->build(Auth::user()));
    }

    /**
     * Vasetra Intelligence Dashboard
     */
    public function intelligence(): View
    {
        $user = Auth::user();
        $companyId = $user->company_id;


        /*
        |--------------------------------------------------------------------------
        | BASE ASSET QUERY
        |--------------------------------------------------------------------------
        */

        $assetQuery = Asset::query()
            ->where('company_id', $companyId);


        /*
        |--------------------------------------------------------------------------
        | BASE MAINTENANCE QUERY
        |--------------------------------------------------------------------------
        */

        $maintenanceQuery = Maintenance::query()
            ->where('company_id', $companyId)
            ->whereIn('status', [
                'scheduled',
                'in_progress',
            ]);


        /*
        |--------------------------------------------------------------------------
        | BASIC SUMMARY
        |--------------------------------------------------------------------------
        */

        $totalAssets = (clone $assetQuery)->count();

        $activeAssets = (clone $assetQuery)
            ->where('status', 'active')
            ->count();

        $myAssets = (clone $assetQuery)
            ->where('responsible_user_id', $user->id)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | DATE
        |--------------------------------------------------------------------------
        */

        $today = now()->startOfDay();

        $endOfWeek = now()->endOfWeek();


        /*
        |--------------------------------------------------------------------------
        | MAINTENANCE SUMMARY
        |--------------------------------------------------------------------------
        */

        $maintenanceOverdue = (clone $maintenanceQuery)
            ->whereDate('maintenance_date', '<', $today)
            ->count();


        $maintenanceToday = (clone $maintenanceQuery)
            ->whereDate('maintenance_date', $today)
            ->count();


        $maintenanceThisWeek = (clone $maintenanceQuery)
            ->whereDate('maintenance_date', '>', $today)
            ->whereDate('maintenance_date', '<=', $endOfWeek)
            ->count();


        $maintenanceDue = $maintenanceOverdue
            + $maintenanceToday
            + $maintenanceThisWeek;


        /*
        |--------------------------------------------------------------------------
        | UNASSIGNED ASSETS
        |--------------------------------------------------------------------------
        */

        $unassignedAssets = (clone $assetQuery)
            ->whereNull('responsible_user_id')
            ->count();


        /*
        |--------------------------------------------------------------------------
        | ASSET CONDITION
        |--------------------------------------------------------------------------
        */

        $assetConditions = (clone $assetQuery)
            ->selectRaw('asset_condition, COUNT(*) as total')
            ->groupBy('asset_condition')
            ->pluck('total', 'asset_condition');


        /*
        |--------------------------------------------------------------------------
        | ASSET CATEGORY
        |--------------------------------------------------------------------------
        */

        $assetCategories = (clone $assetQuery)
            ->with('category')
            ->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | UPCOMING MAINTENANCE
        |--------------------------------------------------------------------------
        */

        $upcomingMaintenance = (clone $maintenanceQuery)
            ->with([
                'asset.category',
                'asset.responsibleUser',
            ])
            ->whereDate('maintenance_date', '>=', $today)
            ->orderBy('maintenance_date')
            ->limit(5)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

        return view('dashboard.intelligence.index', compact(
            'totalAssets',
            'activeAssets',
            'myAssets',

            'maintenanceDue',
            'maintenanceOverdue',
            'maintenanceToday',
            'maintenanceThisWeek',

            'unassignedAssets',

            'assetConditions',
            'assetCategories',

            'upcomingMaintenance'
        ));
    }
}