<?php

namespace App\Http\Controllers\Api\V1\Asset;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Http\Controllers\Api\V1\Maintenance\MaintenanceScheduleController;
use App\Models\Maintenance;
use App\Models\MaintenanceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ALL ASSETS
    |--------------------------------------------------------------------------
    |
    | Menampilkan seluruh asset milik company user yang login.
    |
    */

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Asset::query()
            ->where(
                'company_id',
                $user->company_id
            )
            ->with([
                'category:id,category_name',
                'subCategory:id,sub_category_name',
                'photos:id,asset_id,file_path,original_name,sort_order',
                'branch:id,branch_code,branch_name,is_head_office',
            ])
            ->select([
                'id',
                'company_id',
                'branch_id',
                'responsible_user_id',
                'category_id',
                'sub_category_id',
                'asset_code',
                'asset_name',
                'brand',
                'model',
                'serial_number',
                'description',
                'asset_condition',
                'purchase_date',
                'warranty_start',
                'warranty_end',
                'warranty_note',
                'location',
                'status',
                'disposed_at',
                'maintenance_required',
                'maintenance_type',
                'maintenance_trigger',
                'maintenance_interval',
                'maintenance_interval_unit',
                'maintenance_start_date',
                'last_maintenance_date',
                'next_maintenance_date',
            ])
            ->orderByDesc('id');

        // ?branch_id= : filter per cabang, hanya untuk user yang melihat semua
        // cabang (Administrator Kantor Pusat). User cabang sudah otomatis
        // dibatasi oleh BranchScope, jadi parameter ini diabaikan.
        if ($request->filled('branch_id') && \App\Support\BranchAccess::seesAll($user)) {
            $query->where('branch_id', (int) $request->query('branch_id'));
        }

        // ?status=0|1|2 memfilter status; tanpa itu aset dihapuskan (2) disembunyikan.
        if ($request->filled('status') && in_array((string) $request->query('status'), ['0', '1', '2'], true)) {
            $query->where('status', (int) $request->query('status'));
        } elseif (!$request->boolean('include_disposed')) {
            $query->inService();
        }

        $this->applySearch(
            $query,
            $request
        );

        $assets = $query->paginate(
            $this->perPage($request)
        );

        $data = collect(
            $assets->items()
        )
            ->map(function (Asset $asset) {
                return $this->formatAsset(
                    $asset
                );
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'assets' => $data,
            ],
            'pagination' =>
                $this->pagination($assets),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | MY ASSETS
    |--------------------------------------------------------------------------
    |
    | Hanya asset yang menjadi tanggung jawab user login.
    |
    */

    public function myAssets(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        $query = Asset::query()
            ->where(
                'company_id',
                $user->company_id
            )
            ->where(
                'responsible_user_id',
                $user->id
            )
            ->with([
                'category:id,category_name',
                'subCategory:id,sub_category_name',
                'photos:id,asset_id,file_path,original_name,sort_order',
                'branch:id,branch_code,branch_name,is_head_office',
            ])
            ->select([
                'id',
                'company_id',
                'branch_id',
                'responsible_user_id',
                'category_id',
                'sub_category_id',
                'asset_code',
                'asset_name',
                'brand',
                'model',
                'serial_number',
                'description',
                'asset_condition',
                'purchase_date',
                'warranty_start',
                'warranty_end',
                'warranty_note',
                'location',
                'status',
                'disposed_at',
                'maintenance_required',
                'maintenance_type',
                'maintenance_trigger',
                'maintenance_interval',
                'maintenance_interval_unit',
                'maintenance_start_date',
                'last_maintenance_date',
                'next_maintenance_date',
            ])
            ->orderByDesc('id');

        $this->applySearch(
            $query,
            $request
        );

        $assets = $query->paginate(
            $this->perPage($request)
        );

        $data = collect(
            $assets->items()
        )
            ->map(function (Asset $asset) {
                return $this->formatAsset(
                    $asset
                );
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'assets' => $data,
            ],
            'pagination' =>
                $this->pagination($assets),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | SHOW ALL ASSET
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        $asset = Asset::query()
            ->where(
                'company_id',
                $user->company_id
            )
            ->with([
                'category',
                'subCategory',
                'responsibleUser',
                'photos',
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'asset' =>
                    $this->formatAssetDetail(
                        $asset
                    ),
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | SHOW MY ASSET
    |--------------------------------------------------------------------------
    */

    public function myAssetShow(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        $asset = Asset::query()
            ->where(
                'company_id',
                $user->company_id
            )
            ->where(
                'responsible_user_id',
                $user->id
            )
            ->with([
                'category',
                'subCategory',
                'responsibleUser',
                'photos',
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'asset' =>
                    $this->formatAssetDetail(
                        $asset
                    ),
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ASSET PHOTO
    |--------------------------------------------------------------------------
    |
    | Endpoint khusus untuk mengambil foto asset.
    |
    | Flutter tidak lagi mengambil:
    |
    | /storage/documents/...
    |
    | tetapi:
    |
    | /api/v1/assets/photos/{photoId}
    |
    | Request melewati Laravel sehingga:
    |
    | - Bearer Token tetap digunakan
    | - company isolation tetap berlaku
    | - permission asset.view tetap berlaku
    | - CORS dapat diberikan oleh Laravel
    |
    */

    public function photo(
        Request $request,
        int $photoId
    ) {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Cari asset milik company user
        |--------------------------------------------------------------------------
        */

        $asset = Asset::query()
            ->where(
                'company_id',
                $user->company_id
            )
            // Tanpa asset.view (mis. Karyawan) hanya foto aset yang dipegangnya.
            ->when(
                !$user->hasPermission('asset.view'),
                fn ($query) => $query->where('responsible_user_id', $user->id)
            )
            ->whereHas(
                'photos',
                function ($query) use ($photoId) {
                    $query->where(
                        'id',
                        $photoId
                    );
                }
            )
            ->with([
                'photos' => function ($query) use (
                    $photoId
                ) {
                    $query->where(
                        'id',
                        $photoId
                    );
                },
            ])
            ->first();

        if (!$asset) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil photo
        |--------------------------------------------------------------------------
        */

        $photo =
            $asset->photos->first();

        if (!$photo) {
            abort(404);
        }

        if (
            !$photo->file_path ||
            trim($photo->file_path) === ''
        ) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | Storage path
        |--------------------------------------------------------------------------
        */

        $path = ltrim(
            $photo->file_path,
            '/'
        );

        $disk = Storage::disk(
            'public'
        );

        /*
        |--------------------------------------------------------------------------
        | Pastikan file ada
        |--------------------------------------------------------------------------
        */

        if (!$disk->exists($path)) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | Return file
        |--------------------------------------------------------------------------
        */

        return response()->file(
            $disk->path($path),
            [
                'Content-Type' =>
                    $this->getPhotoMimeType(
                        $path
                    ),

                'Cache-Control' =>
                    'public, max-age=86400',

                'Access-Control-Allow-Origin' =>
                    $request
                        ->headers
                        ->get('Origin')
                    ?? '*',

                'Access-Control-Allow-Methods' =>
                    'GET, OPTIONS',

                'Access-Control-Allow-Headers' =>
                    'Content-Type, Authorization, X-Requested-With, Accept',

                'Access-Control-Expose-Headers' =>
                    'Content-Length, Content-Type',
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PHOTO MIME TYPE
    |--------------------------------------------------------------------------
    */

    private function getPhotoMimeType(
        string $path
    ): string {
        $extension = strtolower(
            pathinfo(
                $path,
                PATHINFO_EXTENSION
            )
        );

        return match ($extension) {

            'jpg',
            'jpeg' =>
                'image/jpeg',

            'png' =>
                'image/png',

            'webp' =>
                'image/webp',

            'gif' =>
                'image/gif',

            default =>
                'application/octet-stream',
        };
    }


    /*
    |--------------------------------------------------------------------------
    | ALL ASSET MAINTENANCE HISTORY
    |--------------------------------------------------------------------------
    */

    public function maintenances(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        $asset = Asset::query()
            ->where(
                'company_id',
                $user->company_id
            )
            ->findOrFail($id);

        return $this->maintenanceResponse(
            $request,
            $asset
        );
    }


    /*
    |--------------------------------------------------------------------------
    | MY ASSET MAINTENANCE HISTORY
    |--------------------------------------------------------------------------
    */

    public function myAssetMaintenances(
        Request $request,
        int $id
    ): JsonResponse {
        $user = $request->user();

        $asset = Asset::query()
            ->where(
                'company_id',
                $user->company_id
            )
            ->where(
                'responsible_user_id',
                $user->id
            )
            ->findOrFail($id);

        return $this->maintenanceResponse(
            $request,
            $asset
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SCAN QR
    |--------------------------------------------------------------------------
    */

    public function scanQr(
        Request $request,
        string $qrToken
    ): JsonResponse {
        $user = $request->user();

        $asset = Asset::query()
            ->where(
                'company_id',
                $user->company_id
            )
            ->where(
                'qr_token',
                $qrToken
            )
            ->with([
                'category',
                'subCategory',
                'responsibleUser',
                'photos',
            ])
            ->first();

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Asset tidak ditemukan atau QR Code tidak valid.',
                'code' =>
                    'ASSET_NOT_FOUND',
            ], 404);
        }

        /*
         * access:
         *  - full    → pemegang aset, atau user dengan izin asset.view
         *              (administrator, teknisi): detail lengkap.
         *  - limited → user lain: info ringkas + boleh "Laporkan masalah"
         *              (POST /assets/{id}/requests, request_type=problem).
         */
        $isMine = (int) $asset->responsible_user_id === (int) $user->id;
        $isFull = $isMine || $user->hasPermission('asset.view');

        return response()->json([
            'success' => true,
            'data' => [
                'access' => $isFull ? 'full' : 'limited',
                'is_mine' => $isMine,
                'asset' => $isFull
                    ? $this->formatAssetDetail($asset)
                    : $this->formatAssetLimited($asset),
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | INFO RINGKAS ASSET (hasil scan oleh user yang bukan pemegang)
    |--------------------------------------------------------------------------
    */

    private function formatAssetLimited(
        Asset $asset
    ): array {
        $activeRequest = $this->getActiveRequest($asset);

        return [
            'id' => $asset->id,
            'asset_code' => $asset->asset_code,
            'asset_name' => $asset->asset_name,
            'category' => $asset->category
                ? ['id' => $asset->category->id, 'name' => $asset->category->category_name]
                : null,
            'sub_category' => $asset->subCategory
                ? ['id' => $asset->subCategory->id, 'name' => $asset->subCategory->sub_category_name]
                : null,
            'brand' => $asset->brand,
            'model' => $asset->model,
            'condition' => $asset->asset_condition,
            'location' => $asset->location,
            'branch' => \App\Http\Controllers\Api\V1\Branch\BranchController::presentBranch($asset->branch),
            'status' => $asset->status,
            'responsible_user' => $asset->responsibleUser
                ? ['id' => $asset->responsibleUser->id, 'name' => $asset->responsibleUser->name]
                : null,
            'has_active_request' => $activeRequest !== null,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | MAINTENANCE RESPONSE
    |--------------------------------------------------------------------------
    */

    private function maintenanceResponse(
        Request $request,
        Asset $asset
    ): JsonResponse {
        /*
         * Sumber data = tabel `maintenances` (sama dengan dashboard).
         * Tabel lama `asset_maintenances` sudah tidak diisi lagi.
         */
        $maintenances =
            Maintenance::query()
                ->where(
                    'company_id',
                    $asset->company_id
                )
                ->where(
                    'asset_id',
                    $asset->id
                )
                ->with([
                    'vendor:id,vendor_name',
                    'creator:id,name',
                    'photos',
                ])
                ->orderByDesc(
                    'maintenance_date'
                )
                ->orderByDesc('id')
                ->paginate(
                    $this->perPage($request)
                );

        $data = collect(
            $maintenances->items()
        )
            ->map(
                fn (Maintenance $maintenance) =>
                    MaintenanceScheduleController::format(
                        $maintenance,
                        true
                    )
            )
            ->values();

        return response()->json([
            'success' => true,

            'data' => [
                'asset' => [
                    'id' =>
                        $asset->id,

                    'asset_code' =>
                        $asset->asset_code,

                    'asset_name' =>
                        $asset->asset_name,
                ],

                'maintenances' =>
                    $data,
            ],

            'pagination' =>
                $this->pagination(
                    $maintenances
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT ASSET LIST
    |--------------------------------------------------------------------------
    */

    private function formatAsset(
        Asset $asset
    ): array {
        $photo =
            $asset->photos->first();

        /*
        |--------------------------------------------------------------------------
        | ACTIVE REQUEST
        |--------------------------------------------------------------------------
        */

        $activeRequest =
            $this->getActiveRequest(
                $asset
            );

        return [
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
                            $asset->category
                                ->category_name,
                    ]
                    : null,

            'sub_category' =>
                $asset->subCategory
                    ? [
                        'id' =>
                            $asset
                                ->subCategory
                                ->id,

                        'name' =>
                            $asset
                                ->subCategory
                                ->sub_category_name,
                    ]
                    : null,

            'brand' =>
                $asset->brand,

            'model' =>
                $asset->model,

            'serial_number' =>
                $asset->serial_number,

            'condition' =>
                $asset->asset_condition,

            'location' =>
                $asset->location,

            'branch' => \App\Http\Controllers\Api\V1\Branch\BranchController::presentBranch($asset->branch),

            'status' =>
                $asset->status,

            'status_label' =>
                $asset->statusLabel(),

            'is_disposed' =>
                $asset->isDisposed(),

            'warranty' =>
                $this->formatWarranty($asset),

            'responsible_user_id' =>
                $asset->responsible_user_id,

            /*
            |--------------------------------------------------------------------------
            | PHOTO URL
            |--------------------------------------------------------------------------
            */

            'photo_url' =>
                $photo
                    ? route(
                        'api.v1.assets.photos',
                        $photo->id
                    )
                    : null,

            /*
            |--------------------------------------------------------------------------
            | MAINTENANCE
            |--------------------------------------------------------------------------
            */

            'maintenance' => [
                'required' =>
                    (bool)
                    $asset->maintenance_required,

                'type' =>
                    $asset->maintenance_type,

                'trigger' =>
                    $asset->maintenance_trigger,

                'interval' =>
                    $asset->maintenance_interval,

                'interval_unit' =>
                    $asset->maintenance_interval_unit,

                'start_date' =>
                    $asset->maintenance_start_date,

                'last_date' =>
                    $asset->last_maintenance_date,

                'next_date' =>
                    $asset->next_maintenance_date,
            ],

            /*
            |--------------------------------------------------------------------------
            | ACTIVE REQUEST
            |--------------------------------------------------------------------------
            */

            'has_active_request' =>
                $activeRequest !== null,

            'active_request' =>
                $activeRequest
                    ? [
                        'id' =>
                            $activeRequest->id,

                        'request_type' =>
                            $activeRequest
                                ->request_type,

                        'description' =>
                            $activeRequest
                                ->description,

                        'status' =>
                            $activeRequest->status,

                        'created_at' =>
                            $activeRequest
                                ->created_at,
                    ]
                    : null,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT ASSET DETAIL
    |--------------------------------------------------------------------------
    */

    private function formatAssetDetail(
        Asset $asset
    ): array {

        /*
        |--------------------------------------------------------------------------
        | PHOTOS
        |--------------------------------------------------------------------------
        */

        $photos = $asset->photos
            ->sortBy('sort_order')
            ->take(3)
            ->map(function ($photo) {
                return [
                    'id' =>
                        $photo->id,

                    'file_path' =>
                        $photo->file_path,

                    'original_name' =>
                        $photo->original_name,

                    'sort_order' =>
                        $photo->sort_order,

                    /*
                    |--------------------------------------------------------------------------
                    | IMPORTANT
                    |--------------------------------------------------------------------------
                    |
                    | Jangan lagi menggunakan:
                    |
                    | asset('storage/...')
                    |
                    | karena Flutter Web akan terkena CORS
                    | pada static storage.
                    |
                    */

                    'url' =>
                        route(
                            'api.v1.assets.photos',
                            $photo->id
                        ),
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | ACTIVE REQUEST
        |--------------------------------------------------------------------------
        */

        $activeRequest =
            $this->getActiveRequest(
                $asset,
                true
            );

        /*
        |--------------------------------------------------------------------------
        | MAINTENANCE HISTORY
        |--------------------------------------------------------------------------
        */

        $maintenanceHistory =
            $asset->maintenances()
                ->with([
                    'vendor:id,vendor_name',
                    'creator:id,name',
                    'photos',
                ])
                ->orderByDesc(
                    'maintenance_date'
                )
                ->orderByDesc('id')
                ->get()
                ->map(
                    fn (Maintenance $maintenance) =>
                        MaintenanceScheduleController::format(
                            $maintenance,
                            true
                        )
                )
                ->values();

        return [
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
                            $asset->category
                                ->category_name,
                    ]
                    : null,

            'sub_category' =>
                $asset->subCategory
                    ? [
                        'id' =>
                            $asset
                                ->subCategory
                                ->id,

                        'name' =>
                            $asset
                                ->subCategory
                                ->sub_category_name,
                    ]
                    : null,

            'responsible_user' =>
                $asset->responsibleUser
                    ? [
                        'id' =>
                            $asset
                                ->responsibleUser
                                ->id,

                        'name' =>
                            $asset
                                ->responsibleUser
                                ->name,
                    ]
                    : null,

            'brand' =>
                $asset->brand,

            'model' =>
                $asset->model,

            'serial_number' =>
                $asset->serial_number,

            'description' =>
                $asset->description,

            'condition' =>
                $asset->asset_condition,

            'purchase_date' =>
                $asset->purchase_date,

            'warranty' => $this->formatWarranty($asset) + [
                'note' =>
                    $asset->warranty_note,
            ],

            'location' =>
                $asset->location,

            // Cabang aset (multi-cabang).
            'branch' => \App\Http\Controllers\Api\V1\Branch\BranchController::presentBranch($asset->branch),

            'status' =>
                $asset->status,

            'status_label' =>
                $asset->statusLabel(),

            'is_disposed' =>
                $asset->isDisposed(),

            // Hanya terisi bila aset dihapuskan (status 2).
            'disposal' =>
                $this->formatDisposal($asset),

            /*
            |--------------------------------------------------------------------------
            | PHOTOS
            |--------------------------------------------------------------------------
            */

            'photos' =>
                $photos,

            /*
            |--------------------------------------------------------------------------
            | MAINTENANCE
            |--------------------------------------------------------------------------
            */

            'maintenance' => [
                'required' =>
                    (bool)
                    $asset->maintenance_required,

                'type' =>
                    $asset->maintenance_type,

                'trigger' =>
                    $asset->maintenance_trigger,

                'interval' =>
                    $asset->maintenance_interval,

                'interval_unit' =>
                    $asset->maintenance_interval_unit,

                'start_date' =>
                    $asset->maintenance_start_date,

                'last_date' =>
                    $asset->last_maintenance_date,

                'next_date' =>
                    $asset->next_maintenance_date,
            ],

            /*
            |--------------------------------------------------------------------------
            | ACTIVE REQUEST
            |--------------------------------------------------------------------------
            */

            'active_request' =>
                $activeRequest
                    ? [
                        'id' =>
                            $activeRequest->id,

                        'request_type' =>
                            $activeRequest
                                ->request_type,

                        'description' =>
                            $activeRequest
                                ->description,

                        'status' =>
                            $activeRequest
                                ->status,

                        'handled_at' =>
                            $activeRequest
                                ->handled_at,

                        'completed_at' =>
                            $activeRequest
                                ->completed_at,

                        'handler' =>
                            $activeRequest->handler
                                ? [
                                    'id' =>
                                        $activeRequest
                                            ->handler
                                            ->id,

                                    'name' =>
                                        $activeRequest
                                            ->handler
                                            ->name,
                                ]
                                : null,
                    ]
                    : null,

            /*
            |--------------------------------------------------------------------------
            | MAINTENANCE HISTORY
            |--------------------------------------------------------------------------
            */

            'maintenance_history' =>
                $maintenanceHistory,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | GET ACTIVE REQUEST
    |--------------------------------------------------------------------------
    */

    private function getActiveRequest(
        Asset $asset,
        bool $withRelations = false
    ): ?MaintenanceRequest {

        $query = MaintenanceRequest::query()
            ->where(
                'company_id',
                $asset->company_id
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
            ->latest('id');

        if ($withRelations) {
            $query->with([
                'requester',
                'handler',
            ]);
        }

        return $query->first();
    }


    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    /**
     * Garansi + status (none | active | expiring | expired) dan sisa hari.
     */
    private function formatWarranty(Asset $asset): array
    {
        $status = $asset->warrantyStatus();

        return [
            'start' => $asset->warranty_start?->toDateString(),
            'end' => $asset->warranty_end?->toDateString(),
            'status' => $status['state'],
            'status_label' => $status['label'],
            'days_left' => $status['days_left'],
        ];
    }

    private function formatDisposal(Asset $asset): ?array
    {
        if (!$asset->isDisposed()) {
            return null;
        }

        $d = $asset->activeDisposal()->with('creator:id,name')->first();

        if (!$d) {
            return ['date' => $asset->disposed_at?->toDateString()];
        }

        return [
            'id' => $d->id,
            'type' => $d->disposal_type,
            'type_label' => $d->typeLabel(),
            'date' => $d->disposal_date->toDateString(),
            'book_value' => $d->book_value !== null ? (float) $d->book_value : null,
            'proceeds' => (float) $d->proceeds,
            'gain_loss' => $d->gain_loss !== null ? (float) $d->gain_loss : null,
            'notes' => $d->notes,
            'document_url' => $d->document_path ? asset('storage/' . $d->document_path) : null,
            'created_by' => $d->creator?->name,
        ];
    }


    private function applySearch(
        $query,
        Request $request
    ): void {

        if (!$request->filled('search')) {
            return;
        }

        $search = trim(
            $request->search
        );

        $query->where(
            function ($q) use ($search) {

                $q->where(
                    'asset_code',
                    'like',
                    "%{$search}%"
                )

                    ->orWhere(
                        'asset_name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'brand',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'model',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'serial_number',
                        'like',
                        "%{$search}%"
                    );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PER PAGE
    |--------------------------------------------------------------------------
    */

    private function perPage(
        Request $request
    ): int {

        return min(
            max(
                $request->integer(
                    'per_page',
                    20
                ),
                1
            ),
            100
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PAGINATION
    |--------------------------------------------------------------------------
    */

    private function pagination(
        $paginator
    ): array {

        return [
            'current_page' =>
                $paginator->currentPage(),

            'last_page' =>
                $paginator->lastPage(),

            'per_page' =>
                $paginator->perPage(),

            'total' =>
                $paginator->total(),

            'from' =>
                $paginator->firstItem(),

            'to' =>
                $paginator->lastItem(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | OLD FILE URL HELPER
    |--------------------------------------------------------------------------
    |
    | Masih dipertahankan agar tidak merusak kode lain.
    | Foto API sekarang menggunakan route() di atas.
    |
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