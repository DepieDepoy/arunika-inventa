<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Maintenance;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
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
        |
        | Jadwal maintenance sekarang mengambil sumber utama dari tabel
        | maintenances, bukan lagi assets.next_maintenance_date.
        |
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
        |
        | Semua angka maintenance sekarang berdasarkan:
        |
        | maintenances.maintenance_date
        |
        */


        /*
        | OVERDUE
        */

        $maintenanceOverdue = (clone $maintenanceQuery)
            ->whereDate('maintenance_date', '<', $today)
            ->count();


        /*
        | TODAY
        */

        $maintenanceToday = (clone $maintenanceQuery)
            ->whereDate('maintenance_date', $today)
            ->count();


        /*
        | THIS WEEK
        |
        | Maintenance setelah hari ini sampai akhir minggu.
        |
        */

        $maintenanceThisWeek = (clone $maintenanceQuery)
            ->whereDate('maintenance_date', '>', $today)
            ->whereDate('maintenance_date', '<=', $endOfWeek)
            ->count();


        /*
        | MAINTENANCE DUE
        |
        | Total maintenance yang sudah overdue + maintenance
        | yang jatuh tempo minggu ini.
        |
        */

        $maintenanceDue = $maintenanceOverdue
            + $maintenanceToday
            + $maintenanceThisWeek;


        /*
        |--------------------------------------------------------------------------
        | UPCOMING MAINTENANCE
        |--------------------------------------------------------------------------
        |
        | Ambil 5 maintenance terdekat dari tabel maintenances.
        |
        | Termasuk:
        | - Today
        | - Tomorrow
        | - Future
        |
        | Tidak mengambil:
        | - completed
        | - cancelled
        |
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
        | MY UPCOMING MAINTENANCE
        |--------------------------------------------------------------------------
        |
        | Hanya maintenance dari asset yang responsible_user_id-nya
        | sama dengan user yang sedang login.
        |
        */

        $myUpcomingMaintenance = (clone $maintenanceQuery)
            ->with([
                'asset.category',
                'asset.responsibleUser',
            ])
            ->whereHas('asset', function ($query) use ($user) {

                $query->where('responsible_user_id', $user->id);

            })
            ->whereDate('maintenance_date', '>=', $today)
            ->orderBy('maintenance_date')
            ->limit(5)
            ->get();


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
        | RECENT ASSETS
        |--------------------------------------------------------------------------
        */

        $recentAssets = (clone $assetQuery)
            ->with([
                'category',
                'responsibleUser',
            ])
            ->latest()
            ->limit(5)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

        return view('dashboard.home.index', compact(
            'totalAssets',
            'activeAssets',
            'myAssets',

            'maintenanceDue',
            'maintenanceOverdue',
            'maintenanceToday',
            'maintenanceThisWeek',

            'upcomingMaintenance',
            'myUpcomingMaintenance',

            'assetConditions',
            'assetCategories',
            'recentAssets'
        ));
    }
}