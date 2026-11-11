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
use App\Services\CompanyStorage;
use App\Services\Maintenance\MaintenanceRequestNotifier;

class MaintenanceRequestController extends Controller
{
    public function __construct(
        protected MaintenanceRequestNotifier $notifier
    ) {
    }

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
                    __('Asset tidak ditemukan atau bukan milik company Anda.'),
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
                    __('Anda tidak memiliki hak untuk membuat request pada asset ini.'),
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
                    __('Asset ini masih memiliki request yang sedang diproses.'),
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

        // Beri tahu tim maintenance bahwa ada request yang menunggu diambil.
        $this->notifier->created($maintenanceRequest);

        return response()->json([
            'success' => true,

            'message' =>
                __('Request berhasil dibuat.'),

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
                    __('Request tidak ditemukan.'),
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
                    __('Anda tidak memiliki hak untuk mengedit request ini.'),
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
                    __('Request ini sudah diproses sehingga tidak dapat diedit.'),
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
                __('Request berhasil diperbarui.'),
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
                    __('Request tidak ditemukan.'),
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS CHECK
        |--------------------------------------------------------------------------
        */

        if ($maintenanceRequest->status !== 'pending') {
            return $this->alreadyTakenResponse($maintenanceRequest);
        }

        /*
        |--------------------------------------------------------------------------
        | TAKE REQUEST
        |--------------------------------------------------------------------------
        |
        | Update bersyarat (status masih pending) supaya dua teknisi yang
        | menekan Take hampir bersamaan tidak sama-sama berhasil.
        |
        */

        $taken = DB::transaction(function () use ($maintenanceRequest, $user) {
            $locked = MaintenanceRequest::query()
                ->whereKey($maintenanceRequest->id)
                ->where('company_id', $user->company_id)
                ->lockForUpdate()
                ->first();

            if (!$locked || $locked->status !== 'pending') {
                return null;
            }

            $locked->update([
                'handled_by' => $user->id,
                'handled_at' => now(),
                'status' => 'in_progress',
            ]);

            return $locked;
        });

        if (!$taken) {
            return $this->alreadyTakenResponse($maintenanceRequest->fresh());
        }

        $this->notifier->taken($taken, $user);

        return response()->json([
            'success' => true,

            'message' =>
                __('Request berhasil diambil dan sedang diproses.'),

            'redirect' =>
                route('maintenance.requests.work', $taken->id),
        ]);
    }


    /**
     * Respons saat request sudah tidak bisa diambil.
     * Menyebutkan siapa yang sedang mengerjakan.
     */
    protected function alreadyTakenResponse(
        ?MaintenanceRequest $maintenanceRequest
    ): \Illuminate\Http\JsonResponse {
        $maintenanceRequest?->loadMissing('handler');

        $message = match ($maintenanceRequest?->status) {
            'in_progress' => 'Request ini sudah diambil dan sedang dikerjakan oleh '
                . ($maintenanceRequest->handler?->name ?? 'teknisi lain') . '.',
            'completed' => 'Request ini sudah selesai dikerjakan.',
            'cancelled' => 'Request ini sudah dibatalkan oleh pemohon.',
            default => 'Request ini sudah diambil atau sudah selesai diproses.',
        };

        return response()->json([
            'success' => false,
            'code' => 'ALREADY_TAKEN',
            'message' => $message,
            'handler' => $maintenanceRequest?->handler
                ? ['id' => $maintenanceRequest->handler->id, 'name' => $maintenanceRequest->handler->name]
                : null,
        ], 409);
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
                    __('Request tidak ditemukan.'),
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
                    __('Progress hanya dapat ditambahkan pada request In Progress.'),
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
                    __('Anda bukan teknisi yang menangani request ini.'),
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
                    __('Asset tidak ditemukan.'),
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
        | STORAGE LIMIT - MAINTENANCE PROGRESS PHOTOS
        |--------------------------------------------------------------------------
        | Progress boleh ditambahkan tanpa foto.
        | Jika ada foto, periksa kuota sebelum membuat log
        | atau menyimpan file fisik.
        |--------------------------------------------------------------------------
        */

        $storageFiles = $request->file('photos', []);

        if (!is_array($storageFiles)) {
            $storageFiles = $storageFiles ? [$storageFiles] : [];
        }

        $storageFiles = array_values(array_filter($storageFiles));

        $storageUsedBytes = CompanyStorage::usedBytes(
            $user->company_id
        );

        $storageLimitBytes = CompanyStorage::limitBytes(
            $user->company_id
        );

        $storageIncomingBytes = CompanyStorage::uploadBytes(
            $storageFiles
        );

        $storageProjectedBytes =
            $storageUsedBytes + $storageIncomingBytes;

        if (
            $storageIncomingBytes > 0
            && $storageProjectedBytes > $storageLimitBytes
        ) {
            $storageRemainingBytes = max(
                0,
                $storageLimitBytes - $storageUsedBytes
            );

            $storageExcessBytes = max(
                0,
                $storageProjectedBytes - $storageLimitBytes
            );

            $message = sprintf(
                'Kapasitas storage tidak mencukupi. Terpakai: %s, kuota: %s, foto progress baru: %s, sisa kuota: %s, perkiraan pemakaian setelah upload: %s, kelebihan kuota: %s. Hapus file yang tidak diperlukan atau upgrade paket.',
                CompanyStorage::formatBytes($storageUsedBytes),
                CompanyStorage::formatBytes($storageLimitBytes),
                CompanyStorage::formatBytes($storageIncomingBytes),
                CompanyStorage::formatBytes($storageRemainingBytes),
                CompanyStorage::formatBytes($storageProjectedBytes),
                CompanyStorage::formatBytes($storageExcessBytes)
            );

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => [
                    'storage_limit' => [$message],
                ],
                'storage' => [
                    'used_bytes' => $storageUsedBytes,
                    'limit_bytes' => $storageLimitBytes,
                    'incoming_bytes' => $storageIncomingBytes,
                    'remaining_bytes' => $storageRemainingBytes,
                    'projected_bytes' => $storageProjectedBytes,
                    'excess_bytes' => $storageExcessBytes,
                ],
            ], 422);
        }

        // Tutup pengecekan storage progress

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

        $this->notifier->progress($maintenanceRequest, $user, $validated['note']);

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'message' =>
                __('Progress berhasil ditambahkan.'),

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
                    __('Request tidak ditemukan.'),
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
                    __('Request harus berstatus In Progress terlebih dahulu.'),
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
                    __('Request ini sedang ditangani oleh user lain.'),
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
                    __('Asset tidak ditemukan.'),
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
                        __('Vendor tidak ditemukan atau bukan milik company Anda.'),
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
        | STORAGE LIMIT - MAINTENANCE RESULT PHOTOS
        |--------------------------------------------------------------------------
        | Hitung seluruh foto hasil maintenance sebelum membuat
        | riwayat maintenance dan menyimpan file fisik.
        |--------------------------------------------------------------------------
        */

        $storageFiles = $request->file('after_photos', []);

        if (!is_array($storageFiles)) {
            $storageFiles = $storageFiles ? [$storageFiles] : [];
        }

        $storageFiles = array_values(array_filter($storageFiles));

        $storageUsedBytes = CompanyStorage::usedBytes(
            $user->company_id
        );

        $storageLimitBytes = CompanyStorage::limitBytes(
            $user->company_id
        );

        $storageIncomingBytes = CompanyStorage::uploadBytes(
            $storageFiles
        );

        $storageProjectedBytes =
            $storageUsedBytes + $storageIncomingBytes;

        if (
            $storageIncomingBytes > 0
            && $storageProjectedBytes > $storageLimitBytes
        ) {
            $storageRemainingBytes = max(
                0,
                $storageLimitBytes - $storageUsedBytes
            );

            $storageExcessBytes = max(
                0,
                $storageProjectedBytes - $storageLimitBytes
            );

            $message = sprintf(
                'Kapasitas storage tidak mencukupi. Terpakai: %s, kuota: %s, foto maintenance baru: %s, sisa kuota: %s, perkiraan pemakaian setelah upload: %s, kelebihan kuota: %s. Hapus file yang tidak diperlukan atau upgrade paket.',
                CompanyStorage::formatBytes($storageUsedBytes),
                CompanyStorage::formatBytes($storageLimitBytes),
                CompanyStorage::formatBytes($storageIncomingBytes),
                CompanyStorage::formatBytes($storageRemainingBytes),
                CompanyStorage::formatBytes($storageProjectedBytes),
                CompanyStorage::formatBytes($storageExcessBytes)
            );

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => [
                    'storage_limit' => [$message],
                ],
                'storage' => [
                    'used_bytes' => $storageUsedBytes,
                    'limit_bytes' => $storageLimitBytes,
                    'incoming_bytes' => $storageIncomingBytes,
                    'remaining_bytes' => $storageRemainingBytes,
                    'projected_bytes' => $storageProjectedBytes,
                    'excess_bytes' => $storageExcessBytes,
                ],
            ], 422);
        }

        // Tutup pengecekan storage maintenance

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

        $this->notifier->completed($maintenanceRequest, $user, $maintenance);

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'message' =>
                __('Maintenance berhasil diselesaikan.'),

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
                    __('Request ini belum berstatus In Progress.')
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

    /**
     * Batalkan maintenance/repair request dari kalender.
     *
     * Hanya pemohon sendiri yang boleh membatalkan request pending.
     */
    public function cancel(
        MaintenanceRequest $maintenanceRequest
    ): \Illuminate\Http\JsonResponse {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('Sesi login telah berakhir. Silakan login kembali.'),
            ], 401);
        }

        $result = DB::transaction(function () use (
            $maintenanceRequest,
            $user
        ) {
            $request = MaintenanceRequest::query()
                ->where('company_id', $user->company_id)
                ->whereKey($maintenanceRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $request->requested_by !== (int) $user->id) {
                return [
                    'success' => false,
                    'status' => 403,
                    'message' => __('Anda hanya dapat membatalkan permintaan milik sendiri.'),
                ];
            }

            $status = strtolower(trim((string) $request->status));

            if ($status !== 'pending') {
                return [
                    'success' => false,
                    'status' => 422,
                    'message' => __('Hanya permintaan berstatus Menunggu yang dapat dibatalkan.'),
                ];
            }

            $request->status = 'cancelled';
            $request->save();

            return [
                'success' => true,
                'status' => 200,
                'message' => __('Permintaan berhasil dibatalkan.'),
                'request' => $request,
            ];
        });

        if ($result['success']) {
            $this->notifier->cancelled($result['request']);
        }

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $result['status']);
    }
        
}
