<?php

namespace App\Http\Controllers\Api\V1\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\MaintenancePhoto;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestLog;
use App\Models\MaintenanceRequestLogPhoto;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MaintenanceRequestController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | USER - LIST REQUEST
    |--------------------------------------------------------------------------
    |
    | User hanya melihat request yang dibuat oleh dirinya sendiri.
    |
    */

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = MaintenanceRequest::query()
            ->where('company_id', $user->company_id)
            ->where('requested_by', $user->id)
            ->with([
                'asset:id,asset_code,asset_name',
                'handler:id,name',
            ])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('request_type')) {
            $query->where(
                'request_type',
                $request->request_type
            );
        }

        $requests = $query->paginate(
            $request->integer('per_page', 20)
        );

        $data = collect($requests->items())
            ->map(function (MaintenanceRequest $maintenanceRequest) {
                return $this->formatRequest(
                    $maintenanceRequest
                );
            })
            ->values();

        return response()->json([
            'success' => true,

            'data' => [
                'requests' => $data,
            ],

            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
                'from' => $requests->firstItem(),
                'to' => $requests->lastItem(),
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | USER - DETAIL REQUEST
    |--------------------------------------------------------------------------
    |
    | User hanya boleh melihat request miliknya sendiri.
    |
    */

    public function show(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        $maintenanceRequest = MaintenanceRequest::query()
            ->where('company_id', $user->company_id)
            ->where('requested_by', $user->id)
            ->with([
                'asset.category',
                'asset.subCategory',
                'asset.photos',

                'requester:id,name,email,phone',

                'handler:id,name,email,phone',

                'maintenance.photos',
                'maintenance.vendor',
                'maintenance.creator',

                'logs.user:id,name',
                'logs.photos',
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,

            'data' => [
                'request' => $this->formatRequestDetail(
                    $maintenanceRequest
                ),
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | USER - CREATE MAINTENANCE REQUEST
    |--------------------------------------------------------------------------
    */

    public function storeMaintenanceRequest(
        Request $request,
        int $assetId
    ): JsonResponse {
        return $this->createRequest(
            $request,
            $assetId,
            'maintenance'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | USER - CREATE REPAIR REQUEST
    |--------------------------------------------------------------------------
    */

    public function storeRepairRequest(
        Request $request,
        int $assetId
    ): JsonResponse {
        return $this->createRequest(
            $request,
            $assetId,
            'repair'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | USER - CREATE REQUEST CORE
    |--------------------------------------------------------------------------
    */

    private function createRequest(
        Request $request,
        int $assetId,
        string $requestType
    ): JsonResponse {
        $user = $request->user();

        $validated = $request->validate([
            'description' => [
                'required',
                'string',
                'min:5',
                'max:5000',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | ASSET CHECK
        |--------------------------------------------------------------------------
        */

        $asset = Asset::query()
            ->where('id', $assetId)
            ->where('company_id', $user->company_id)
            ->where(
                'responsible_user_id',
                $user->id
            )
            ->first();

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Asset tidak ditemukan atau Anda bukan responsible user asset ini.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | ACTIVE REQUEST CHECK
        |--------------------------------------------------------------------------
        */

        $activeRequest = MaintenanceRequest::query()
            ->where(
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
            ->latest('id')
            ->first();

        if ($activeRequest) {
            return response()->json([
                'success' => false,

                'message' =>
                    'Asset ini masih memiliki request yang sedang diproses.',

                'data' => [
                    'request' => [
                        'id' =>
                            $activeRequest->id,

                        'request_type' =>
                            $activeRequest->request_type,

                        'status' =>
                            $activeRequest->status,
                    ],
                ],
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE
        |--------------------------------------------------------------------------
        */

        $maintenanceRequest = DB::transaction(
            function () use (
                $user,
                $asset,
                $validated,
                $requestType
            ) {
                return MaintenanceRequest::create([
                    'company_id' =>
                        $user->company_id,

                    'asset_id' =>
                        $asset->id,

                    'requested_by' =>
                        $user->id,

                    'handled_by' =>
                        null,

                    'request_type' =>
                        $requestType,

                    'description' =>
                        $validated['description'],

                    'status' =>
                        'pending',

                    'handled_at' =>
                        null,

                    'completed_at' =>
                        null,
                ]);
            }
        );

        $maintenanceRequest->load([
            'asset:id,asset_code,asset_name',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                $requestType === 'repair'
                    ? 'Repair request berhasil dibuat.'
                    : 'Maintenance request berhasil dibuat.',

            'data' => [
                'request' =>
                    $this->formatRequest(
                        $maintenanceRequest
                    ),
            ],
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | TEAM - LIST REQUEST
    |--------------------------------------------------------------------------
    |
    | Tim Maintenance melihat seluruh request dalam company.
    |
    | Default:
    | pending + in_progress
    |
    | Bisa filter status jika diperlukan.
    |
    */

    public function teamIndex(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        $query = MaintenanceRequest::query()
            ->where(
                'company_id',
                $user->company_id
            )
            ->with([
                'asset:id,asset_code,asset_name,responsible_user_id',
                'asset.responsibleUser:id,name',
                'requester:id,name',
                'handler:id,name',
            ])
            ->orderByRaw(
                "CASE
                    WHEN status = 'pending' THEN 1
                    WHEN status = 'in_progress' THEN 2
                    ELSE 3
                END"
            )
            ->orderByDesc('id');

        /*
        |--------------------------------------------------------------------------
        | DEFAULT ACTIVE REQUEST
        |--------------------------------------------------------------------------
        */

        if (!$request->filled('status')) {
            $query->whereIn(
                'status',
                [
                    'pending',
                    'in_progress',
                ]
            );
        } else {
            $query->where(
                'status',
                $request->status
            );
        }

        if ($request->filled('request_type')) {
            $query->where(
                'request_type',
                $request->request_type
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim(
                $request->search
            );

            $query->where(function ($q) use ($search) {
                $q->whereHas(
                    'asset',
                    function ($assetQuery) use ($search) {
                        $assetQuery
                            ->where(
                                'asset_code',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'asset_name',
                                'like',
                                "%{$search}%"
                            );
                    }
                )
                ->orWhereHas(
                    'requester',
                    function ($userQuery) use ($search) {
                        $userQuery->where(
                            'name',
                            'like',
                            "%{$search}%"
                        );
                    }
                )
                ->orWhere(
                    'description',
                    'like',
                    "%{$search}%"
                );
            });
        }

        $requests = $query->paginate(
            $request->integer(
                'per_page',
                20
            )
        );

        $data = collect($requests->items())
            ->map(function (
                MaintenanceRequest $maintenanceRequest
            ) {
                return $this->formatTeamRequest(
                    $maintenanceRequest
                );
            })
            ->values();

        return response()->json([
            'success' => true,

            'data' => [
                'requests' => $data,
            ],

            'pagination' => [
                'current_page' =>
                    $requests->currentPage(),

                'last_page' =>
                    $requests->lastPage(),

                'per_page' =>
                    $requests->perPage(),

                'total' =>
                    $requests->total(),

                'from' =>
                    $requests->firstItem(),

                'to' =>
                    $requests->lastItem(),
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | TEAM - DETAIL REQUEST
    |--------------------------------------------------------------------------
    */

    public function teamShow(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        $maintenanceRequest = MaintenanceRequest::query()
            ->where(
                'company_id',
                $user->company_id
            )
            ->with([
                'asset.category',
                'asset.subCategory',
                'asset.photos',
                'asset.responsibleUser:id,name,email,phone',

                'requester:id,name,email,phone',

                'handler:id,name,email,phone',

                'maintenance.photos',
                'maintenance.vendor',
                'maintenance.creator',

                'logs.user:id,name,email,phone',
                'logs.photos',
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,

            'data' => [
                'request' =>
                    $this->formatTeamRequestDetail(
                        $maintenanceRequest
                    ),
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | TEAM - TAKE REQUEST
    |--------------------------------------------------------------------------
    |
    | pending -> in_progress
    |
    | User yang melakukan Take otomatis menjadi handler.
    |
    */

    public function take(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        $maintenanceRequest = MaintenanceRequest::query()
            ->where(
                'company_id',
                $user->company_id
            )
            ->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | STATUS CHECK
        |--------------------------------------------------------------------------
        */

        if (
            $maintenanceRequest->status !==
            'pending'
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'Request ini sudah diambil atau sudah selesai diproses.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | TAKE
        |--------------------------------------------------------------------------
        |
        | Conditional update supaya dua teknisi yang menekan Take hampir
        | bersamaan tidak sama-sama berhasil.
        |
        */

        $updated = MaintenanceRequest::query()
            ->where(
                'id',
                $maintenanceRequest->id
            )
            ->where(
                'company_id',
                $user->company_id
            )
            ->where(
                'status',
                'pending'
            )
            ->update([
                'handled_by' =>
                    $user->id,

                'handled_at' =>
                    now(),

                'status' =>
                    'in_progress',

                'updated_at' =>
                    now(),
            ]);

        if ($updated !== 1) {
            return response()->json([
                'success' => false,

                'message' =>
                    'Request sudah lebih dahulu diambil oleh teknisi lain.',
            ], 409);
        }

        $maintenanceRequest->refresh();

        $maintenanceRequest->load([
            'asset:id,asset_code,asset_name',
            'requester:id,name',
            'handler:id,name',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'Request berhasil diambil dan sedang diproses.',

            'data' => [
                'request' =>
                    $this->formatTeamRequest(
                        $maintenanceRequest
                    ),
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | TEAM - ADD PROGRESS
    |--------------------------------------------------------------------------
    |
    | Request harus:
    | - in_progress
    | - handled_by = user login
    |
    | Maksimal 10 foto setiap progress.
    |
    */

    public function progress(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        $maintenanceRequest = MaintenanceRequest::query()
            ->where(
                'company_id',
                $user->company_id
            )
            ->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | STATUS CHECK
        |--------------------------------------------------------------------------
        */

        if (
            $maintenanceRequest->status !==
            'in_progress'
        ) {
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
                'max:5000',
            ],

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
        | ASSET
        |--------------------------------------------------------------------------
        */

        $asset = Asset::query()
            ->where(
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
        */

        $company = $user->company;

        $companyCode =
            $company?->company_code
            ?? Str::slug(
                $company?->company_name
                    ?? 'company'
            );

        $datePath = now()->format(
            'Y/m'
        );

        /*
        |--------------------------------------------------------------------------
        | CREATE LOG + PHOTOS
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
                | DIRECTORY
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
                | MULTIPLE PHOTOS
                |--------------------------------------------------------------------------
                */

                if (
                    $request->hasFile(
                        'photos'
                    )
                ) {
                    foreach (
                        $request->file('photos')
                        as $photo
                    ) {
                        $extension =
                            strtolower(
                                $photo->getClientOriginalExtension()
                            );

                        $filename =
                            $asset->asset_code .
                            '-' .
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
        | LOAD RESULT
        |--------------------------------------------------------------------------
        */

        $log->load([
            'user:id,name',
            'photos',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'Progress berhasil ditambahkan.',

            'data' => [
                'progress' =>
                    $this->formatProgress(
                        $log
                    ),
            ],
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | TEAM - COMPLETE REQUEST
    |--------------------------------------------------------------------------
    |
    | in_progress -> completed
    |
    | Membuat record Maintenance.
    |
    | Foto hasil masuk ke:
    | maintenance_photos
    |
    | photo_type = after
    |
    */

    public function complete(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        $maintenanceRequest = MaintenanceRequest::query()
            ->where(
                'company_id',
                $user->company_id
            )
            ->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | STATUS CHECK
        |--------------------------------------------------------------------------
        */

        if (
            $maintenanceRequest->status !==
            'in_progress'
        ) {
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
        |
        | Mengikuti validation web.
        |
        */

        $validated = $request->validate([
            'action_taken' => [
                'required',
                'string',
                'min:5',
                'max:5000',
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
                'max:5000',
            ],

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

        $asset = Asset::query()
            ->where(
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

        if (
            !empty(
                $validated['vendor_id']
            )
        ) {
            $vendorExists = Vendor::query()
                ->where(
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
            $company?->company_code
            ?? Str::slug(
                $company?->company_name
                    ?? 'company'
            );

        $datePath = now()->format(
            'Y/m'
        );

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
            Maintenance::query()
                ->where(
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
        | CREATE MAINTENANCE
        |--------------------------------------------------------------------------
        */

        $maintenance = DB::transaction(
            function () use (
                $validated,
                $request,
                $user,
                $maintenanceRequest,
                $asset,
                $maintenanceCode,
                $companyCode,
                $datePath
            ) {
                /*
                |--------------------------------------------------------------------------
                | CREATE MAINTENANCE HISTORY
                |--------------------------------------------------------------------------
                */

                $maintenance = Maintenance::create([
                    'company_id' =>
                        $user->company_id,

                    'maintenance_request_id' =>
                        $maintenanceRequest->id,

                    'asset_id' =>
                        $asset->id,

                    'maintenance_code' =>
                        $maintenanceCode,

                    /*
                    | Request dari user berupa maintenance/repair/problem/other.
                    |
                    | Untuk hasil maintenance request kita mengikuti logic web:
                    | corrective.
                    */

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
                        $validated[
                            'next_maintenance_date'
                        ] ?? null,

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
                | SAVE MULTIPLE AFTER PHOTOS
                |--------------------------------------------------------------------------
                */

                foreach (
                    $request->file(
                        'after_photos'
                    ) as $photo
                ) {
                    $extension =
                        strtolower(
                            $photo->getClientOriginalExtension()
                        );

                    $filename =
                        $asset->asset_code .
                        '-' .
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
                | UPDATE ASSET
                |--------------------------------------------------------------------------
                */

                $asset->update([
                    'last_maintenance_date' =>
                        $validated[
                            'maintenance_date'
                        ],

                    'next_maintenance_date' =>
                        $validated[
                            'next_maintenance_date'
                        ] ?? null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | COMPLETE REQUEST
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
        | LOAD RESULT
        |--------------------------------------------------------------------------
        */

        $maintenance->load([
            'photos',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'Maintenance berhasil diselesaikan.',

            'data' => [
                'maintenance' => [
                    'id' =>
                        $maintenance->id,

                    'maintenance_code' =>
                        $maintenance->maintenance_code,

                    'maintenance_type' =>
                        $maintenance->maintenance_type,

                    'maintenance_date' =>
                        $maintenance->maintenance_date,

                    'problem_description' =>
                        $maintenance->problem_description,

                    'action_taken' =>
                        $maintenance->action_taken,

                    'technician_name' =>
                        $maintenance->technician_name,

                    'vendor_id' =>
                        $maintenance->vendor_id,

                    'cost' =>
                        $maintenance->cost,

                    'status' =>
                        $maintenance->status,

                    'next_maintenance_date' =>
                        $maintenance->next_maintenance_date,

                    'notes' =>
                        $maintenance->notes,

                    'photos' =>
                        $maintenance->photos
                            ->map(
                                function (
                                    MaintenancePhoto $photo
                                ) {
                                    return [
                                        'id' =>
                                            $photo->id,

                                        'photo_type' =>
                                            $photo->photo_type,

                                        'file_path' =>
                                            $photo->file_path,

                                        'caption' =>
                                            $photo->caption,

                                        'url' =>
                                            $this->fileUrl(
                                                $photo->file_path
                                            ),
                                    ];
                                }
                            )
                            ->values(),
                ],
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT USER REQUEST
    |--------------------------------------------------------------------------
    */

    private function formatRequest(
        MaintenanceRequest $maintenanceRequest
    ): array {
        return [
            'id' =>
                $maintenanceRequest->id,

            'request_type' =>
                $maintenanceRequest->request_type,

            'description' =>
                $maintenanceRequest->description,

            'status' =>
                $maintenanceRequest->status,

            'handled_at' =>
                $maintenanceRequest->handled_at,

            'completed_at' =>
                $maintenanceRequest->completed_at,

            'created_at' =>
                $maintenanceRequest->created_at,

            'updated_at' =>
                $maintenanceRequest->updated_at,

            'asset' =>
                $maintenanceRequest->asset
                    ? [
                        'id' =>
                            $maintenanceRequest
                                ->asset
                                ->id,

                        'asset_code' =>
                            $maintenanceRequest
                                ->asset
                                ->asset_code,

                        'asset_name' =>
                            $maintenanceRequest
                                ->asset
                                ->asset_name,
                    ]
                    : null,

            'handler' =>
                $maintenanceRequest->handler
                    ? [
                        'id' =>
                            $maintenanceRequest
                                ->handler
                                ->id,

                        'name' =>
                            $maintenanceRequest
                                ->handler
                                ->name,
                    ]
                    : null,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT TEAM REQUEST
    |--------------------------------------------------------------------------
    */

    private function formatTeamRequest(
        MaintenanceRequest $maintenanceRequest
    ): array {
        return [
            'id' =>
                $maintenanceRequest->id,

            'request_type' =>
                $maintenanceRequest->request_type,

            'description' =>
                $maintenanceRequest->description,

            'status' =>
                $maintenanceRequest->status,

            'handled_at' =>
                $maintenanceRequest->handled_at,

            'completed_at' =>
                $maintenanceRequest->completed_at,

            'created_at' =>
                $maintenanceRequest->created_at,

            'updated_at' =>
                $maintenanceRequest->updated_at,

            'asset' =>
                $maintenanceRequest->asset
                    ? [
                        'id' =>
                            $maintenanceRequest
                                ->asset
                                ->id,

                        'asset_code' =>
                            $maintenanceRequest
                                ->asset
                                ->asset_code,

                        'asset_name' =>
                            $maintenanceRequest
                                ->asset
                                ->asset_name,

                        'responsible_user' =>
                            $maintenanceRequest
                                ->asset
                                ->responsibleUser
                                ? [
                                    'id' =>
                                        $maintenanceRequest
                                            ->asset
                                            ->responsibleUser
                                            ->id,

                                    'name' =>
                                        $maintenanceRequest
                                            ->asset
                                            ->responsibleUser
                                            ->name,
                                ]
                                : null,
                    ]
                    : null,

            'requester' =>
                $maintenanceRequest->requester
                    ? [
                        'id' =>
                            $maintenanceRequest
                                ->requester
                                ->id,

                        'name' =>
                            $maintenanceRequest
                                ->requester
                                ->name,
                    ]
                    : null,

            'handler' =>
                $maintenanceRequest->handler
                    ? [
                        'id' =>
                            $maintenanceRequest
                                ->handler
                                ->id,

                        'name' =>
                            $maintenanceRequest
                                ->handler
                                ->name,
                    ]
                    : null,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT USER DETAIL
    |--------------------------------------------------------------------------
    */

    private function formatRequestDetail(
        MaintenanceRequest $maintenanceRequest
    ): array {
        return [
            'id' =>
                $maintenanceRequest->id,

            'request_type' =>
                $maintenanceRequest->request_type,

            'description' =>
                $maintenanceRequest->description,

            'status' =>
                $maintenanceRequest->status,

            'handled_at' =>
                $maintenanceRequest->handled_at,

            'completed_at' =>
                $maintenanceRequest->completed_at,

            'created_at' =>
                $maintenanceRequest->created_at,

            'updated_at' =>
                $maintenanceRequest->updated_at,

            'asset' =>
                $this->formatAsset(
                    $maintenanceRequest->asset
                ),

            'requester' =>
                $this->formatUser(
                    $maintenanceRequest->requester,
                    true
                ),

            'handler' =>
                $this->formatUser(
                    $maintenanceRequest->handler,
                    true
                ),

            'maintenance' =>
                $this->formatMaintenance(
                    $maintenanceRequest->maintenance
                ),

            'logs' =>
                $maintenanceRequest->logs
                    ->map(
                        function (
                            MaintenanceRequestLog $log
                        ) {
                            return $this->formatProgress(
                                $log
                            );
                        }
                    )
                    ->values(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT TEAM DETAIL
    |--------------------------------------------------------------------------
    */

    private function formatTeamRequestDetail(
        MaintenanceRequest $maintenanceRequest
    ): array {
        return [
            'id' =>
                $maintenanceRequest->id,

            'request_type' =>
                $maintenanceRequest->request_type,

            'description' =>
                $maintenanceRequest->description,

            'status' =>
                $maintenanceRequest->status,

            'handled_at' =>
                $maintenanceRequest->handled_at,

            'completed_at' =>
                $maintenanceRequest->completed_at,

            'created_at' =>
                $maintenanceRequest->created_at,

            'updated_at' =>
                $maintenanceRequest->updated_at,

            'asset' =>
                $this->formatAsset(
                    $maintenanceRequest->asset,
                    true
                ),

            'requester' =>
                $this->formatUser(
                    $maintenanceRequest->requester,
                    true
                ),

            'handler' =>
                $this->formatUser(
                    $maintenanceRequest->handler,
                    true
                ),

            'maintenance' =>
                $this->formatMaintenance(
                    $maintenanceRequest->maintenance
                ),

            'logs' =>
                $maintenanceRequest->logs
                    ->map(
                        function (
                            MaintenanceRequestLog $log
                        ) {
                            return $this->formatProgress(
                                $log
                            );
                        }
                    )
                    ->values(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT ASSET
    |--------------------------------------------------------------------------
    */

    private function formatAsset(
        $asset,
        bool $includeResponsibleUser = false
    ): ?array {
        if (!$asset) {
            return null;
        }

        $data = [
            'id' =>
                $asset->id,

            'asset_code' =>
                $asset->asset_code,

            'asset_name' =>
                $asset->asset_name,

            'category' =>
                $asset->category
                    ? [
                        'id' =>
                            $asset->category->id,

                        'name' =>
                            $asset->category->category_name,
                    ]
                    : null,

            'sub_category' =>
                $asset->subCategory
                    ? [
                        'id' =>
                            $asset->subCategory->id,

                        'name' =>
                            $asset->subCategory
                                ->sub_category_name,
                    ]
                    : null,

            'photos' =>
                $asset->photos
                    ->map(
                        function ($photo) {
                            return [
                                'id' =>
                                    $photo->id,

                                'original_name' =>
                                    $photo->original_name,

                                'file_path' =>
                                    $photo->file_path,

                                'sort_order' =>
                                    $photo->sort_order,

                                'url' =>
                                    $this->fileUrl(
                                        $photo->file_path
                                    ),
                            ];
                        }
                    )
                    ->values(),
        ];

        if ($includeResponsibleUser) {
            $data['responsible_user'] =
                $asset->responsibleUser
                    ? [
                        'id' =>
                            $asset->responsibleUser->id,

                        'name' =>
                            $asset->responsibleUser->name,

                        'email' =>
                            $asset->responsibleUser->email,

                        'phone' =>
                            $asset->responsibleUser->phone,
                    ]
                    : null;
        }

        return $data;
    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT USER
    |--------------------------------------------------------------------------
    */

    private function formatUser(
        $user,
        bool $detail = false
    ): ?array {
        if (!$user) {
            return null;
        }

        $data = [
            'id' =>
                $user->id,

            'name' =>
                $user->name,
        ];

        if ($detail) {
            $data['email'] =
                $user->email;

            $data['phone'] =
                $user->phone;
        }

        return $data;
    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT MAINTENANCE
    |--------------------------------------------------------------------------
    */

    private function formatMaintenance(
        $maintenance
    ): ?array {
        if (!$maintenance) {
            return null;
        }

        return [
            'id' =>
                $maintenance->id,

            'maintenance_code' =>
                $maintenance->maintenance_code,

            'maintenance_type' =>
                $maintenance->maintenance_type,

            'maintenance_date' =>
                $maintenance->maintenance_date,

            'problem_description' =>
                $maintenance->problem_description,

            'action_taken' =>
                $maintenance->action_taken,

            'technician_name' =>
                $maintenance->technician_name,

            'vendor_id' =>
                $maintenance->vendor_id,

            'cost' =>
                $maintenance->cost,

            'status' =>
                $maintenance->status,

            'next_maintenance_date' =>
                $maintenance->next_maintenance_date,

            'notes' =>
                $maintenance->notes,

            'photos' =>
                $maintenance->photos
                    ? $maintenance->photos
                        ->map(
                            function (
                                MaintenancePhoto $photo
                            ) {
                                return [
                                    'id' =>
                                        $photo->id,

                                    'photo_type' =>
                                        $photo->photo_type,

                                    'file_path' =>
                                        $photo->file_path,

                                    'caption' =>
                                        $photo->caption,

                                    'url' =>
                                        $this->fileUrl(
                                            $photo->file_path
                                        ),
                                ];
                            }
                        )
                        ->values()
                    : [],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT PROGRESS
    |--------------------------------------------------------------------------
    */

    private function formatProgress(
        MaintenanceRequestLog $log
    ): array {
        return [
            'id' =>
                $log->id,

            'note' =>
                $log->note,

            'created_at' =>
                $log->created_at,

            'user' =>
                $log->user
                    ? [
                        'id' =>
                            $log->user->id,

                        'name' =>
                            $log->user->name,
                    ]
                    : null,

            'photos' =>
                $log->photos
                    ->map(
                        function (
                            MaintenanceRequestLogPhoto $photo
                        ) {
                            return [
                                'id' =>
                                    $photo->id,

                                'file_path' =>
                                    $photo->file_path,

                                'caption' =>
                                    $photo->caption,

                                'uploaded_by' =>
                                    $photo->uploaded_by,

                                'url' =>
                                    $this->fileUrl(
                                        $photo->file_path
                                    ),
                            ];
                        }
                    )
                    ->values(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | FILE URL
    |--------------------------------------------------------------------------
    */

    private function fileUrl(
        ?string $path
    ): ?string {
        if (!$path) {
            return null;
        }

        return asset(
            'storage/' .
            ltrim(
                $path,
                '/'
            )
        );
    }
}