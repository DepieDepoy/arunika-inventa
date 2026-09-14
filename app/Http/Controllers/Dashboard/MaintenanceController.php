<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\MaintenanceRequest;
use App\Models\Vendor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Yajra\DataTables\Facades\DataTables;

class MaintenanceController extends Controller
{
    /**
     * ============================================================================
     * ALL MAINTENANCE
     * ============================================================================
     *
     * Menampilkan seluruh asset perusahaan yang memiliki
     * scheduled maintenance.
     *
     * Kategori:
     * - Overdue
     * - Today
     * - This Week
     * - Upcoming
     */
    public function index()
{
    /** @var \App\Models\User $user */
    $user = Auth::user();

    $today = now()->startOfDay();
    $endOfWeek = now()->endOfWeek();

    /*
    |--------------------------------------------------------------------------
    | BASE QUERY
    |--------------------------------------------------------------------------
    | Ambil maintenance schedule dari tabel maintenances.
    | Hanya maintenance yang masih aktif:
    | - scheduled
    | - in_progress
    |
    */

    $baseQuery = Maintenance::with([
        'asset.category',
        'asset.subCategory',
        'asset.responsibleUser',
        'vendor',
    ])
        ->where('company_id', $user->company_id)
        ->whereIn('status', [
            'scheduled',
            'in_progress',
        ]);

    /*
    |--------------------------------------------------------------------------
    | OVERDUE
    |--------------------------------------------------------------------------
    */

    $overdue = (clone $baseQuery)
        ->whereDate(
            'maintenance_date',
            '<',
            $today
        )
        ->orderBy('maintenance_date')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | TODAY
    |--------------------------------------------------------------------------
    */

    $todayMaintenance = (clone $baseQuery)
        ->whereDate(
            'maintenance_date',
            $today
        )
        ->orderBy('maintenance_date')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | THIS WEEK
    |--------------------------------------------------------------------------
    | Besok sampai akhir minggu.
    | Hari ini dipisahkan agar tidak double count.
    */

    $thisWeek = (clone $baseQuery)
        ->whereDate(
            'maintenance_date',
            '>',
            $today
        )
        ->whereDate(
            'maintenance_date',
            '<=',
            $endOfWeek
        )
        ->orderBy('maintenance_date')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | UPCOMING
    |--------------------------------------------------------------------------
    | Maintenance setelah minggu berjalan.
    */

    $upcoming = (clone $baseQuery)
        ->whereDate(
            'maintenance_date',
            '>',
            $endOfWeek
        )
        ->orderBy('maintenance_date')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | SUMMARY
    |--------------------------------------------------------------------------
    */

    $summary = [
        'overdue' => $overdue->count(),
        'today' => $todayMaintenance->count(),
        'this_week' => $thisWeek->count(),
        'upcoming' => $upcoming->count(),

        'total' => $overdue->count()
            + $todayMaintenance->count()
            + $thisWeek->count()
            + $upcoming->count(),
    ];

    return view(
        'dashboard.maintenance.index',
        compact(
            'overdue',
            'todayMaintenance',
            'thisWeek',
            'upcoming',
            'summary'
        )
    );
}
    /**
     * ========================================================================
     * CREATE MAINTENANCE
     * ========================================================================
     *
     * Legacy create maintenance form.
     *
     * Tetap dipertahankan untuk sementara.
     */
    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $assets = Asset::where(
                'company_id',
                $user->company_id
            )
            ->where('status', 1)
            ->orderBy('asset_name')
            ->get();

        $vendors = Vendor::where(
                'company_id',
                $user->company_id
            )
            ->where('status', 1)
            ->orderBy('vendor_name')
            ->get();

        return view(
            'dashboard.maintenance.create',
            compact(
                'assets',
                'vendors'
            )
        );
    }


    /**
     * ========================================================================
     * STORE MAINTENANCE
     * ========================================================================
     *
     * Legacy direct maintenance creation.
     *
     * Tetap dipertahankan untuk sementara.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'asset_id' => [
                'required',
                'integer',
            ],

            'maintenance_type' => [
                'required',
                'in:preventive,corrective,inspection,calibration',
            ],

            'maintenance_date' => [
                'required',
                'date',
            ],

            'problem_description' => [
                'nullable',
                'string',
            ],

            'action_taken' => [
                'nullable',
                'string',
            ],

            'technician_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'vendor_id' => [
                'nullable',
                'integer',
            ],

            'cost' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'status' => [
                'required',
                'in:scheduled,in_progress,completed,cancelled',
            ],

            'next_maintenance_date' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | CHECK ASSET
        |--------------------------------------------------------------------------
        */

        $asset = Asset::where(
                'id',
                $validated['asset_id']
            )
            ->where(
                'company_id',
                $user->company_id
            )
            ->first();

        if (!$asset) {
            return back()
                ->withInput()
                ->withErrors([
                    'asset_id' =>
                        'Asset tidak ditemukan atau bukan milik perusahaan Anda.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK VENDOR
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['vendor_id'])) {

            $vendorExists = Vendor::where(
                    'id',
                    $validated['vendor_id']
                )
                ->where(
                    'company_id',
                    $user->company_id
                )
                ->exists();

            if (!$vendorExists) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'vendor_id' =>
                            'Vendor tidak ditemukan atau bukan milik perusahaan Anda.',
                    ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | GENERATE MAINTENANCE CODE
        |--------------------------------------------------------------------------
        */

        $currentYear = now()->format('Y');

        $lastMaintenance = Maintenance::where(
                'company_id',
                $user->company_id
            )
            ->whereYear(
                'created_at',
                $currentYear
            )
            ->orderByDesc('id')
            ->first();

        if ($lastMaintenance) {

            preg_match(
                '/(\d{5})$/',
                $lastMaintenance->maintenance_code,
                $matches
            );

            $lastNumber = isset($matches[1])
                ? (int) $matches[1]
                : 0;

            $nextNumber = $lastNumber + 1;

        } else {

            $nextNumber = 1;
        }

        $maintenanceCode =
            'MNT-' .
            $currentYear .
            '-' .
            str_pad(
                $nextNumber,
                5,
                '0',
                STR_PAD_LEFT
            );


        /*
        |--------------------------------------------------------------------------
        | CREATE MAINTENANCE
        |--------------------------------------------------------------------------
        */

        $maintenance = Maintenance::create([
            'company_id' =>
                $user->company_id,

            /*
            |--------------------------------------------------------------------------
            | Direct maintenance lama tidak memiliki request.
            |--------------------------------------------------------------------------
            */

            'maintenance_request_id' =>
                null,

            'asset_id' =>
                $asset->id,

            'maintenance_code' =>
                $maintenanceCode,

            'maintenance_type' =>
                $validated['maintenance_type'],

            'maintenance_date' =>
                $validated['maintenance_date'],

            'problem_description' =>
                $validated['problem_description'] ?? null,

            'action_taken' =>
                $validated['action_taken'] ?? null,

            'technician_name' =>
                $validated['technician_name'] ?? null,

            'vendor_id' =>
                $validated['vendor_id'] ?? null,

            'cost' =>
                $validated['cost'] ?? 0,

            'status' =>
                $validated['status'],

            'next_maintenance_date' =>
                $validated['next_maintenance_date'] ?? null,

            'notes' =>
                $validated['notes'] ?? null,

            'created_by' =>
                $user->id,
        ]);


        /*
        |--------------------------------------------------------------------------
        | UPDATE ASSET MAINTENANCE DATA
        |--------------------------------------------------------------------------
        */

        if ($validated['status'] === 'completed') {

            $asset->update([
                'last_maintenance_date' =>
                    $validated['maintenance_date'],

                'next_maintenance_date' =>
                    $validated['next_maintenance_date'] ?? null,
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | REDIRECT
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('maintenance.index')
            ->with(
                'success',
                'Maintenance berhasil ditambahkan.'
            );
    }


    /**
     * ========================================================================
     * MY ASSETS
     * ========================================================================
     *
     * Menampilkan asset yang menjadi tanggung jawab user login.
     *
     * User hanya dapat membuat maintenance request terhadap asset ini.
     */
    public function myAssets()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $assets = Asset::with([
            'category',
            'subcategory',
            'vendor',

            /*
            |--------------------------------------------------------------------------
            | ACTIVE MAINTENANCE REQUEST
            |--------------------------------------------------------------------------
            |
            | Mengambil request aktif dari masing-masing asset.
            |
            */

            'activeMaintenanceRequest',
        ])
            ->where(
                'company_id',
                $user->company_id
            )
            ->where(
                'responsible_user_id',
                $user->id
            )
            ->orderBy('asset_name')
            ->get();

        return view(
            'dashboard.maintenance.my-assets',
            compact('assets')
        );
    }


    /**
     * ========================================================================
     * DATATABLES AJAX
     * ========================================================================
     *
     * Legacy maintenance DataTables.
     *
     * Tetap dipertahankan sementara agar halaman lama tidak rusak.
     */
    public function data(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = Maintenance::with([
            'asset',
            'vendor',
        ])
            ->where(
                'company_id',
                $user->company_id
            )
            ->latest();


        /*
        |--------------------------------------------------------------------------
        | FILTER ASSET
        |--------------------------------------------------------------------------
        */

        if ($request->filled('asset_id')) {

            $query->where(
                'asset_id',
                $request->asset_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER TYPE
        |--------------------------------------------------------------------------
        */

        if ($request->filled('maintenance_type')) {

            $query->where(
                'maintenance_type',
                $request->maintenance_type
            );
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER STATUS
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }


        /*
        |--------------------------------------------------------------------------
        | DATATABLE
        |--------------------------------------------------------------------------
        */

        return DataTables::of($query)

            ->addIndexColumn()


            /*
            |--------------------------------------------------------------------------
            | ASSET INFO
            |--------------------------------------------------------------------------
            */

            ->addColumn(
                'asset_info',
                function ($row) {

                    if (!$row->asset) {
                        return '-';
                    }

                    return '
                        <div>
                            <div class="fw-semibold">
                                ' . e($row->asset->asset_name) . '
                            </div>

                            <small class="text-muted">
                                ' . e($row->asset->asset_code) . '
                            </small>
                        </div>
                    ';
                }
            )


            /*
            |--------------------------------------------------------------------------
            | MAINTENANCE TYPE
            |--------------------------------------------------------------------------
            */

            ->addColumn(
                'maintenance_type_label',
                function ($row) {

                    return match ($row->maintenance_type) {

                        'preventive' =>
                            '<span class="badge bg-info">
                                Preventive
                            </span>',

                        'corrective' =>
                            '<span class="badge bg-warning">
                                Corrective
                            </span>',

                        'inspection' =>
                            '<span class="badge bg-primary">
                                Inspection
                            </span>',

                        'calibration' =>
                            '<span class="badge bg-secondary">
                                Calibration
                            </span>',

                        default =>
                            '<span class="badge bg-light text-dark">
                                -
                            </span>',
                    };
                }
            )


            /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */

            ->addColumn(
                'status_label',
                function ($row) {

                    return match ($row->status) {

                        'scheduled' =>
                            '<span class="badge bg-info">
                                Scheduled
                            </span>',

                        'in_progress' =>
                            '<span class="badge bg-warning">
                                In Progress
                            </span>',

                        'completed' =>
                            '<span class="badge bg-success">
                                Completed
                            </span>',

                        'cancelled' =>
                            '<span class="badge bg-danger">
                                Cancelled
                            </span>',

                        default =>
                            '<span class="badge bg-light text-dark">
                                -
                            </span>',
                    };
                }
            )


            /*
            |--------------------------------------------------------------------------
            | VENDOR
            |--------------------------------------------------------------------------
            */

            ->addColumn(
                'vendor_name',
                function ($row) {

                    return $row->vendor
                        ? e($row->vendor->vendor_name)
                        : '-';
                }
            )


            /*
            |--------------------------------------------------------------------------
            | MAINTENANCE DATE
            |--------------------------------------------------------------------------
            */

            ->editColumn(
                'maintenance_date',
                function ($row) {

                    return $row->maintenance_date
                        ? $row->maintenance_date->format('d M Y')
                        : '-';
                }
            )


            /*
            |--------------------------------------------------------------------------
            | COST
            |--------------------------------------------------------------------------
            */

            ->editColumn(
                'cost',
                function ($row) {

                    return 'Rp ' .
                        number_format(
                            $row->cost,
                            0,
                            ',',
                            '.'
                        );
                }
            )


            /*
            |--------------------------------------------------------------------------
            | ACTION
            |--------------------------------------------------------------------------
            */

            ->addColumn(
                'action',
                function ($row) use ($user) {

                    $action = '
                        <div class="d-flex justify-content-center align-items-center gap-1">
                    ';


                    /*
                    |--------------------------------------------------------------------------
                    | VIEW
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $user->hasPermission(
                            'maintenance.view'
                        )
                    ) {

                        $action .= '
                            <a
                                href="javascript:void(0)"
                                class="btn-action btn-view"
                                data-id="' . $row->id . '"
                                title="View"
                            >
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        ';
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | EDIT
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $user->hasPermission(
                            'maintenance.edit'
                        )
                    ) {

                        $action .= '
                            <a
                                href="javascript:void(0)"
                                class="btn-action btn-edit"
                                data-id="' . $row->id . '"
                                title="Edit"
                            >
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                        ';
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | DELETE
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $user->hasPermission(
                            'maintenance.delete'
                        )
                    ) {

                        $action .= '
                            <a
                                href="javascript:void(0)"
                                class="btn-action btn-delete"
                                data-id="' . $row->id . '"
                                title="Delete"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        ';
                    }


                    $action .= '
                        </div>
                    ';

                    return $action;
                }
            )


            /*
            |--------------------------------------------------------------------------
            | RAW HTML COLUMNS
            |--------------------------------------------------------------------------
            */

            ->rawColumns([
                'asset_info',
                'maintenance_type_label',
                'status_label',
                'action',
            ])

            ->make(true);
    }


    /**
     * ========================================================================
     * MAINTENANCE HISTORY
     * ========================================================================
     *
     * Menampilkan request maintenance yang sudah selesai.
     *
     * Struktur baru:
     *
     * MaintenanceRequest
     *      ↓
     * Maintenance
     *
     * Satu request completed memiliki satu hasil maintenance.
     *
     * Jadi kita TIDAK lagi mencari Maintenance berdasarkan asset_id.
     */
    public function history()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $requests = MaintenanceRequest::with([
            /*
            |--------------------------------------------------------------------------
            | ASSET
            |--------------------------------------------------------------------------
            */

            'asset.category',
            'asset.subcategory',
            'asset.responsibleUser',

            /*
            |--------------------------------------------------------------------------
            | REQUEST USERS
            |--------------------------------------------------------------------------
            */

            'requester',
            'handler',

            /*
            |--------------------------------------------------------------------------
            | PROGRESS
            |--------------------------------------------------------------------------
            */

            'logs.user',
            'logs.photos',

            /*
            |--------------------------------------------------------------------------
            | EXACT MAINTENANCE RESULT
            |--------------------------------------------------------------------------
            */

            'maintenance.photos',
            'maintenance.vendor',
            'maintenance.creator',
        ])
            ->where(
                'company_id',
                $user->company_id
            )
            ->where(
                'status',
                'completed'
            )
            ->latest('completed_at')
            ->latest('id')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | REQUEST COUNT PER ASSET
        |--------------------------------------------------------------------------
        |
        | Dipakai untuk informasi histori asset.
        |
        */

        $assetRequestCounts = MaintenanceRequest::selectRaw(
            'asset_id, COUNT(*) as total_requests'
        )
            ->where(
                'company_id',
                $user->company_id
            )
            ->whereIn(
                'status',
                [
                    'pending',
                    'in_progress',
                    'completed',
                ]
            )
            ->groupBy('asset_id')
            ->pluck(
                'total_requests',
                'asset_id'
            );


        /*
        |--------------------------------------------------------------------------
        | VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'dashboard.maintenance.history',
            compact(
                'requests',
                'assetRequestCounts'
            )
        );
    }
}

