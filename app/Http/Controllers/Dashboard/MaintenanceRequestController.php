<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\MaintenanceRequest;
use App\Models\MaintenancePhoto;
use App\Models\MaintenanceRequestLog;
use App\Models\MaintenanceRequestLogPhoto;
use App\Models\Vendor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MaintenanceRequestController extends Controller
{
    /**
     * ========================================================================
     * MAINTENANCE REQUESTS
     * ========================================================================
     *
     * Menampilkan request maintenance yang masih aktif dalam company.
     *
     * Status aktif:
     * - pending
     * - in_progress
     *
     * Status completed otomatis hilang dari halaman ini dan masuk History.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $requests = MaintenanceRequest::with([
            'asset',
            'requester',
            'handler',
        ])
            ->where(
                'company_id',
                $user->company_id
            )
            ->whereIn(
                'status',
                [
                    'pending',
                    'in_progress',
                ]
            )
            ->latest()
            ->get();

        return view(
            'dashboard.maintenance.requests.index',
            compact('requests')
        );
    }


    /**
     * ========================================================================
     * CREATE REQUEST
     * ========================================================================
     *
     * Request dibuat dari halaman My Assets.
     *
     * User hanya boleh membuat request terhadap asset yang:
     * - berada pada company yang sama
     * - responsible_user_id = user yang login
     *
     * Satu asset tidak boleh memiliki lebih dari satu active request.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'asset_id' => [
                'required',
                'integer',
                'exists:assets,id',
            ],

            'request_type' => [
                'required',
                'in:maintenance,repair,problem,other',
            ],

            'description' => [
                'required',
                'string',
                'min:5',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | ASSET COMPANY CHECK
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
            return response()->json([
                'success' => false,
                'message' =>
                    'Asset tidak ditemukan atau bukan milik company Anda.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIBLE USER CHECK
        |--------------------------------------------------------------------------
        */

        if (
            (int) $asset->responsible_user_id !==
            (int) $user->id
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Anda tidak memiliki hak untuk membuat request pada asset ini.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | ACTIVE REQUEST CHECK
        |--------------------------------------------------------------------------
        */

        $activeRequest = MaintenanceRequest::where(
                'company_id',
                $user->company_id
            )
            ->where(
                'asset_id',
                $asset->id
            )
            ->whereIn(
                'status',
                [
                    'pending',
                    'in_progress',
                ]
            )
            ->first();

        if ($activeRequest) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Asset ini masih memiliki request yang sedang diproses.',
                'request_id' =>
                    $activeRequest->id,
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE REQUEST
        |--------------------------------------------------------------------------
        */

        $maintenanceRequest = DB::transaction(
            function () use (
                $validated,
                $user,
                $asset
            ) {
                return MaintenanceRequest::create([
                    'company_id' =>
                        $user->company_id,

                    'asset_id' =>
                        $asset->id,

                    'requested_by' =>
                        $user->id,

                    'request_type' =>
                        $validated['request_type'],

                    'description' =>
                        $validated['description'],

                    'status' =>
                        'pending',
                ]);
            }
        );

        return response()->json([
            'success' => true,

            'message' =>
                'Request berhasil dibuat.',

            'data' => [
                'id' =>
                    $maintenanceRequest->id,

                'status' =>
                    $maintenanceRequest->status,
            ],
        ]);
    }


    /**
     * ========================================================================
     * UPDATE REQUEST
     * ========================================================================
     *
     * Request hanya dapat diedit oleh requester sendiri.
     *
     * Hanya status pending yang dapat diedit.
     */
    public function update(
        Request $request,
        MaintenanceRequest $maintenanceRequest
    ) {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | COMPANY CHECK
        |--------------------------------------------------------------------------
        */

        if (
            (int) $maintenanceRequest->company_id !==
            (int) $user->company_id
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Request tidak ditemukan.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | REQUESTER CHECK
        |--------------------------------------------------------------------------
        */

        if (
            (int) $maintenanceRequest->requested_by !==
            (int) $user->id
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Anda tidak memiliki hak untuk mengedit request ini.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS CHECK
        |--------------------------------------------------------------------------
        */

        if ($maintenanceRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' =>
                    'Request ini sudah diproses sehingga tidak dapat diedit.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'request_type' => [
                'required',
                'in:maintenance,repair,problem,other',
            ],

            'description' => [
                'required',
                'string',
                'min:5',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */

        $maintenanceRequest->update([
            'request_type' =>
                $validated['request_type'],

            'description' =>
                $validated['description'],
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'Request berhasil diperbarui.',
        ]);
    }


    /**
     * ========================================================================
     * TAKE REQUEST
     * ========================================================================
     *
     * pending -> in_progress
     *
     * User yang melakukan Take otomatis menjadi handler.
     */
    public function take(
        MaintenanceRequest $maintenanceRequest
    ) {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | COMPANY CHECK
        |--------------------------------------------------------------------------
        */

        if (
            (int) $maintenanceRequest->company_id !==
            (int) $user->company_id
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Request tidak ditemukan.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS CHECK
        |--------------------------------------------------------------------------
        */

        if ($maintenanceRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' =>
                    'Request ini sudah diambil atau sudah selesai diproses.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | TAKE REQUEST
        |--------------------------------------------------------------------------
        */

        $maintenanceRequest->update([
            'handled_by' =>
                $user->id,

            'handled_at' =>
                now(),

            'status' =>
                'in_progress',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'Request berhasil diambil dan sedang diproses.',
        ]);
    }


    /**
     * ========================================================================
     * ADD PROGRESS
     * ========================================================================
     *
     * Progress dapat ditambahkan berkali-kali.
     *
     * Setiap progress:
     * - membuat 1 MaintenanceRequestLog
     * - dapat memiliki maksimal 10 foto
     *
     * Storage:
     *
     * storage/app/public/documents/{companyCode}/maintenance/progress/YYYY/MM/
     *
     * Contoh:
     *
     * documents/ptpesona/maintenance/progress/2026/09/AST-001-uuid.jpg
     */
    public function addProgress(
        Request $request,
        MaintenanceRequest $maintenanceRequest
    ) {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | COMPANY CHECK
        |--------------------------------------------------------------------------
        */

        if (
            (int) $maintenanceRequest->company_id !==
            (int) $user->company_id
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Request tidak ditemukan.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS CHECK
        |--------------------------------------------------------------------------
        */

        if ($maintenanceRequest->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' =>
                    'Progress hanya dapat ditambahkan pada request In Progress.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | HANDLER CHECK
        |--------------------------------------------------------------------------
        */

        if (
            (int) $maintenanceRequest->handled_by !==
            (int) $user->id
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Anda bukan teknisi yang menangani request ini.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'note' => [
                'required',
                'string',
                'min:5',
            ],

            /*
            |--------------------------------------------------------------------------
            | MULTIPLE PROGRESS PHOTOS
            |--------------------------------------------------------------------------
            |
            | Setiap kali progress dikirim, user dapat upload maksimal 10 foto.
            |
            | Progress berikutnya dapat dikirim lagi kapan saja selama request
            | masih berstatus in_progress.
            |
            */

            'photos' => [
                'nullable',
                'array',
                'max:10',
            ],

            'photos.*' => [
                'image',
                'mimes:jpg,jpeg,png',
                'max:10240',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | LOAD ASSET
        |--------------------------------------------------------------------------
        */

        $asset = Asset::where(
                'id',
                $maintenanceRequest->asset_id
            )
            ->where(
                'company_id',
                $user->company_id
            )
            ->first();

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Asset tidak ditemukan.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | COMPANY CODE
        |--------------------------------------------------------------------------
        |
        | Gunakan company code yang sudah tersedia pada company.
        |
        | Jika project menggunakan field company_code pada Company,
        | field tersebut diprioritaskan.
        |
        */

        $company = $user->company;

        $companyCode =
            $company->company_code
            ?? Str::slug(
                $company->company_name ?? 'company'
            );

        /*
        |--------------------------------------------------------------------------
        | DATE PATH
        |--------------------------------------------------------------------------
        */

        $datePath = now()->format('Y/m');

        /*
        |--------------------------------------------------------------------------
        | SAVE PROGRESS
        |--------------------------------------------------------------------------
        */

        $log = DB::transaction(
            function () use (
                $validated,
                $request,
                $maintenanceRequest,
                $user,
                $asset,
                $companyCode,
                $datePath
            ) {

                /*
                |--------------------------------------------------------------------------
                | CREATE LOG
                |--------------------------------------------------------------------------
                */

                $log = MaintenanceRequestLog::create([
                    'maintenance_request_id' =>
                        $maintenanceRequest->id,

                    'user_id' =>
                        $user->id,

                    'note' =>
                        $validated['note'],
                ]);

                /*
                |--------------------------------------------------------------------------
                | PROGRESS PHOTO DIRECTORY
                |--------------------------------------------------------------------------
                */

                $destinationPath =
                    storage_path(
                        'app/public/documents/' .
                        $companyCode .
                        '/maintenance/progress/' .
                        $datePath
                    );

                if (!is_dir($destinationPath)) {
                    mkdir(
                        $destinationPath,
                        0755,
                        true
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | SAVE MULTIPLE PROGRESS PHOTOS
                |--------------------------------------------------------------------------
                */

                if ($request->hasFile('photos')) {

                    foreach (
                        $request->file('photos')
                        as $photo
                    ) {

                        $extension =
                            $photo->getClientOriginalExtension();

                        $filename =
                            $asset->asset_code . '-' .
                            Str::uuid() .
                            '.' .
                            $extension;

                        $photo->move(
                            $destinationPath,
                            $filename
                        );

                        $filePath =
                            'documents/' .
                            $companyCode .
                            '/maintenance/progress/' .
                            $datePath .
                            '/' .
                            $filename;

                        MaintenanceRequestLogPhoto::create([
                            'maintenance_request_log_id' =>
                                $log->id,

                            'file_path' =>
                                $filePath,

                            'caption' =>
                                null,

                            'uploaded_by' =>
                                $user->id,
                        ]);
                    }
                }

                return $log;
            }
        );

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'message' =>
                'Progress berhasil ditambahkan.',

            'data' => [
                'id' =>
                    $log->id,

                'created_at' =>
                    $log->created_at?->format(
                        'Y-m-d H:i:s'
                    ),
            ],
        ]);
    }


    /**
     * ========================================================================
     * COMPLETE REQUEST
     * ========================================================================
     *
     * Request diselesaikan oleh handler.
     *
     * Setelah selesai:
     *
     * MaintenanceRequest.status = completed
     *
     * Maka request:
     * - hilang dari Maintenance Requests
     * - tersedia di Maintenance History
     *
     * Result photo:
     * - dapat lebih dari 1 foto
     * - maksimal 10 foto
     *
     * Storage:
     *
     * storage/app/public/documents/{companyCode}/maintenance/result/YYYY/MM/
     */
    public function complete(
        Request $request,
        MaintenanceRequest $maintenanceRequest
    ) {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | COMPANY CHECK
        |--------------------------------------------------------------------------
        */

        if (
            (int) $maintenanceRequest->company_id !==
            (int) $user->company_id
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Request tidak ditemukan.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS CHECK
        |--------------------------------------------------------------------------
        */

        if ($maintenanceRequest->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' =>
                    'Request harus berstatus In Progress terlebih dahulu.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | HANDLER CHECK
        |--------------------------------------------------------------------------
        */

        if (
            (int) $maintenanceRequest->handled_by !==
            (int) $user->id
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Request ini sedang ditangani oleh user lain.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'action_taken' => [
                'required',
                'string',
                'min:5',
            ],

            'technician_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'vendor_id' => [
                'nullable',
                'integer',
                'exists:vendors,id',
            ],

            'cost' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'maintenance_date' => [
                'required',
                'date',
            ],

            'next_maintenance_date' => [
                'nullable',
                'date',
                'after_or_equal:maintenance_date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | RESULT PHOTOS
            |--------------------------------------------------------------------------
            |
            | Sebelumnya hanya 1 after_photo.
            |
            | Sekarang dapat lebih dari 1 foto hasil maintenance.
            |
            */

            'after_photos' => [
                'required',
                'array',
                'min:1',
                'max:10',
            ],

            'after_photos.*' => [
                'image',
                'mimes:jpg,jpeg,png',
                'max:10240',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | ASSET CHECK
        |--------------------------------------------------------------------------
        */

        $asset = Asset::where(
                'id',
                $maintenanceRequest->asset_id
            )
            ->where(
                'company_id',
                $user->company_id
            )
            ->first();

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Asset tidak ditemukan.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | VENDOR COMPANY CHECK
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
                return response()->json([
                    'success' => false,
                    'message' =>
                        'Vendor tidak ditemukan atau bukan milik company Anda.',
                ], 422);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | COMPANY CODE
        |--------------------------------------------------------------------------
        */

        $company = $user->company;

        $companyCode =
            $company->company_code
            ?? Str::slug(
                $company->company_name ?? 'company'
            );

        /*
        |--------------------------------------------------------------------------
        | DATE PATH
        |--------------------------------------------------------------------------
        */

        $datePath = now()->format('Y/m');

        /*
        |--------------------------------------------------------------------------
        | GENERATE MAINTENANCE CODE
        |--------------------------------------------------------------------------
        */

        do {

            $maintenanceCode =
                'MNT-' .
                now()->format('Ymd') .
                '-' .
                strtoupper(
                    Str::random(6)
                );

        } while (
            Maintenance::where(
                'company_id',
                $user->company_id
            )
                ->where(
                    'maintenance_code',
                    $maintenanceCode
                )
                ->exists()
        );

        /*
        |--------------------------------------------------------------------------
        | CREATE MAINTENANCE HISTORY
        |--------------------------------------------------------------------------
        */

        $maintenance = DB::transaction(
            function () use (
                $validated,
                $user,
                $maintenanceRequest,
                $asset,
                $maintenanceCode,
                $request,
                $companyCode,
                $datePath
            ) {

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
                    | LINK REQUEST
                    |--------------------------------------------------------------------------
                    */

                    'maintenance_request_id' =>
                        $maintenanceRequest->id,

                    'asset_id' =>
                        $asset->id,

                    'maintenance_code' =>
                        $maintenanceCode,

                    'maintenance_type' =>
                        'corrective',

                    'maintenance_date' =>
                        $validated['maintenance_date'],

                    'problem_description' =>
                        $maintenanceRequest->description,

                    'action_taken' =>
                        $validated['action_taken'],

                    'technician_name' =>
                        $validated['technician_name']
                        ?? $user->name,

                    'vendor_id' =>
                        $validated['vendor_id']
                        ?? null,

                    'cost' =>
                        $validated['cost']
                        ?? 0,

                    'status' =>
                        'completed',

                    'next_maintenance_date' =>
                        $validated['next_maintenance_date']
                        ?? null,

                    'notes' =>
                        $validated['notes']
                        ?? null,

                    'created_by' =>
                        $user->id,
                ]);

                /*
                |--------------------------------------------------------------------------
                | RESULT PHOTO DIRECTORY
                |--------------------------------------------------------------------------
                */

                $destinationPath =
                    storage_path(
                        'app/public/documents/' .
                        $companyCode .
                        '/maintenance/result/' .
                        $datePath
                    );

                if (!is_dir($destinationPath)) {
                    mkdir(
                        $destinationPath,
                        0755,
                        true
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | SAVE MULTIPLE RESULT PHOTOS
                |--------------------------------------------------------------------------
                */

                foreach (
                    $request->file('after_photos')
                    as $photo
                ) {

                    $extension =
                        $photo->getClientOriginalExtension();

                    $filename =
                        $asset->asset_code . '-' .
                        Str::uuid() .
                        '.' .
                        $extension;

                    $photo->move(
                        $destinationPath,
                        $filename
                    );

                    $filePath =
                        'documents/' .
                        $companyCode .
                        '/maintenance/result/' .
                        $datePath .
                        '/' .
                        $filename;

                    MaintenancePhoto::create([
                        'maintenance_id' =>
                            $maintenance->id,

                        'photo_type' =>
                            'after',

                        'file_path' =>
                            $filePath,

                        'caption' =>
                            'Foto hasil maintenance',

                        'uploaded_by' =>
                            $user->id,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | UPDATE ASSET MAINTENANCE DATA
                |--------------------------------------------------------------------------
                */

                $asset->update([
                    'last_maintenance_date' =>
                        $validated['maintenance_date'],

                    'next_maintenance_date' =>
                        $validated['next_maintenance_date']
                        ?? null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | UPDATE REQUEST
                |--------------------------------------------------------------------------
                */

                $maintenanceRequest->update([
                    'status' =>
                        'completed',

                    'completed_at' =>
                        now(),
                ]);

                return $maintenance;
            }
        );

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'message' =>
                'Maintenance berhasil diselesaikan.',

            'data' => [
                'maintenance_id' =>
                    $maintenance->id,

                'maintenance_code' =>
                    $maintenance->maintenance_code,
            ],
        ]);
    }


    /**
     * ========================================================================
     * WORK PAGE
     * ========================================================================
     *
     * Halaman kerja untuk handler request.
     */
    public function work(
        MaintenanceRequest $maintenanceRequest
    ) {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | COMPANY CHECK
        |--------------------------------------------------------------------------
        */

        if (
            (int) $maintenanceRequest->company_id !==
            (int) $user->company_id
        ) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS CHECK
        |--------------------------------------------------------------------------
        */

        if ($maintenanceRequest->status !== 'in_progress') {
            return redirect()
                ->route(
                    'maintenance.requests.index'
                )
                ->with(
                    'error',
                    'Request ini belum berstatus In Progress.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | HANDLER CHECK
        |--------------------------------------------------------------------------
        */

        if (
            (int) $maintenanceRequest->handled_by !==
            (int) $user->id
        ) {
            abort(
                403,
                'Request ini sedang ditangani oleh user lain.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | LOAD RELATIONSHIPS
        |--------------------------------------------------------------------------
        */

        $maintenanceRequest->load([
            'asset.category',
            'asset.subcategory',
            'requester',
            'handler',
            'logs.user',
            'logs.photos',
        ]);

        /*
        |--------------------------------------------------------------------------
        | VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'dashboard.maintenance.requests.work',
            compact('maintenanceRequest')
        );
    }


    /**
     * ========================================================================
     * SHOW REQUEST DETAIL
     * ========================================================================
     *
     * Bisa digunakan untuk request aktif maupun completed.
     *
     * Hasil maintenance sekarang diambil berdasarkan
     * maintenance_request_id.
     */
    public function show(
        MaintenanceRequest $maintenanceRequest
    ) {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | COMPANY CHECK
        |--------------------------------------------------------------------------
        */

        if (
            (int) $maintenanceRequest->company_id !==
            (int) $user->company_id
        ) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | LOAD REQUEST RELATIONSHIPS
        |--------------------------------------------------------------------------
        */

        $maintenanceRequest->load([
            'asset.category',
            'asset.subcategory',
            'requester',
            'handler',
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
        ]);

        /*
        |--------------------------------------------------------------------------
        | GET MAINTENANCE RESULT
        |--------------------------------------------------------------------------
        */

        $maintenance =
            $maintenanceRequest->maintenance;

        /*
        |--------------------------------------------------------------------------
        | VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'dashboard.maintenance.requests.show',
            compact(
                'maintenanceRequest',
                'maintenance'
            )
        );
    }
}
