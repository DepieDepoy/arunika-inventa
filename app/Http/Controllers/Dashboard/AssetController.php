<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetPhoto;
use App\Models\AssetDocument;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Maintenance;
use App\Helpers\CodeHelper;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

use App\Exports\AssetImportTemplateExport;
use App\Imports\AssetImport;
use App\Imports\AssetPreviewImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\ExcelPreviewService;
use App\Jobs\ProcessAssetImport;
use App\Models\ImportHistory;

Carbon::setLocale('id');

class AssetController extends Controller
{
    /**
     * Display Asset page
     */
    public function index()
    {
        $companyId = Auth::user()->company_id;

        $categories = Category::where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('category_name')
            ->get();

        $subCategories = SubCategory::where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('sub_category_name')
            ->get();

        $vendors = Vendor::where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('vendor_name')
            ->get();

        $users = User::where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return view('dashboard.asset.index', compact(
            'categories',
            'subCategories',
            'vendors',
            'users'
        ));
    }


    /**
     * Create Asset
     */
    public function create()
    {
        $companyId = Auth::user()->company_id;

        $categories = Category::where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('category_name')
            ->get();

        $subCategories = SubCategory::where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('sub_category_name')
            ->get();

        $vendors = Vendor::where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('vendor_name')
            ->get();

        $users = User::where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return view('dashboard.asset.create', compact(
            'categories',
            'subCategories',
            'vendors',
            'users'
        ));
    }


    /**
     * DataTables
     */
    public function data(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $query = Asset::query()
            ->where('company_id', $companyId)
            ->with([
                'category',
                'subCategory',
                'vendor',
                'responsibleUser',
            ]);

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search_asset')) {

            $search = $request->search_asset;

            $query->where(function ($q) use ($search) {

                $q->where('asset_code', 'like', "%{$search}%")
                    ->orWhere('asset_name', 'like', "%{$search}%");

            });
        }


        /*
        |--------------------------------------------------------------------------
        | CATEGORY
        |--------------------------------------------------------------------------
        */

        if ($request->filled('category_id')) {

            $query->where(
                'category_id',
                $request->category_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SUB CATEGORY
        |--------------------------------------------------------------------------
        */

        if ($request->filled('sub_category_id')) {

            $query->where(
                'sub_category_id',
                $request->sub_category_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | VENDOR
        |--------------------------------------------------------------------------
        */

        if ($request->filled('vendor_id')) {

            $query->where(
                'vendor_id',
                $request->vendor_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled('status') &&
            $request->status !== ''
        ) {

            $query->where(
                'status',
                $request->status
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CREATED DATE FROM
        |--------------------------------------------------------------------------
        */

        if ($request->filled('created_from')) {

            $query->whereDate(
                'created_at',
                '>=',
                $request->created_from
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CREATED DATE TO
        |--------------------------------------------------------------------------
        */

        if ($request->filled('created_to')) {

            $query->whereDate(
                'created_at',
                '<=',
                $request->created_to
            );
        }


        /*
        |--------------------------------------------------------------------------
        | DATATABLE
        |--------------------------------------------------------------------------
        */

        return DataTables::eloquent($query)

            ->addIndexColumn()

            ->addColumn('checkbox', function ($asset) {

                return '
                    <div class="text-center">

                        <input
                            type="checkbox"
                            class="form-check-input asset-checkbox"
                            value="' . $asset->id . '"
                        >

                    </div>
                ';
            })

            ->addColumn('category_name', function ($asset) {

                return $asset->category
                    ? $asset->category->category_name
                    : '-';
            })

            ->addColumn('sub_category_name', function ($asset) {

                return $asset->subCategory
                    ? $asset->subCategory->sub_category_name
                    : '-';
            })

            ->addColumn('vendor_name', function ($asset) {

                return $asset->vendor
                    ? $asset->vendor->vendor_name
                    : '-';
            })

            ->addColumn('responsible_name', function ($asset) {

                return $asset->responsibleUser
                    ? $asset->responsibleUser->name
                    : '-';
            })

            ->editColumn('purchase_date', function ($asset) {

                if (!$asset->purchase_date) {
                    return '-';
                }

                return Carbon::parse(
                    $asset->purchase_date
                )->translatedFormat('d F Y');
            })

            ->editColumn('purchase_price', function ($asset) {

                if ($asset->purchase_price === null) {
                    return '-';
                }

                return 'Rp ' . number_format(
                    $asset->purchase_price,
                    0,
                    ',',
                    '.'
                );
            })

            ->editColumn('status', function ($asset) {

                if ($asset->status == 1) {

                    return '
                        <span class="badge bg-success">
                            Active
                        </span>
                    ';
                }

                return '
                    <span class="badge bg-secondary">
                        Inactive
                    </span>
                ';
            })

            ->addColumn('action', function ($asset) {

                return '
                    <div class="d-flex gap-1">

                        <a
                            href="' . route(
                                'assets.show',
                                $asset->id
                            ) . '"
                            class="btn btn-sm btn-info"
                            title="View"
                        >
                            <i class="fa fa-eye"></i>
                        </a>

                        <a
                            href="' . route(
                                'assets.edit',
                                $asset->id
                            ) . '"
                            class="btn btn-sm btn-warning"
                            title="Edit"
                        >
                            <i class="fa fa-edit"></i>
                        </a>

                        <button
                            type="button"
                            class="btn btn-sm btn-danger btn-delete"
                            data-id="' . $asset->id . '"
                            title="Delete"
                        >
                            <i class="fa fa-trash"></i>
                        </button>

                    </div>
                ';
            })

            ->rawColumns([
                'checkbox',
                'status',
                'action'
            ])

            ->make(true);
    }


    /**
     * =========================================================================
     * STORE ASSET
     * =========================================================================
     */
    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $validator = Validator::make(
            $request->all(),
            [
                'asset_name' => [
                    'required',
                    'string',
                    'max:255'
                ],
                'category_id' => [
                    'required'
                ],
                'sub_category_id' => [
                    'nullable'
                ],
                'vendor_id' => [
                    'required',
                    'exists:vendors,id'
                ],
                'responsible_user_id' => [
                    'nullable',
                    'exists:users,id'
                ],
                'brand' => [
                    'nullable',
                    'string',
                    'max:100'
                ],
                'model' => [
                    'nullable',
                    'string',
                    'max:150'
                ],
                'serial_number' => [
                    'nullable',
                    'string',
                    'max:150'
                ],
                'description' => [
                    'nullable',
                    'string'
                ],
                'asset_condition' => [
                    'nullable',
                    'string'
                ],
                'purchase_date' => [
                    'nullable',
                    'date'
                ],
                'purchase_price' => [
                    'nullable',
                    'numeric',
                    'min:0'
                ],
                'purchase_invoice' => [
                    'nullable',
                    'string',
                    'max:100'
                ],
                'depreciation_method' => [
                    'nullable',
                    'string',
                    'max:50'
                ],
                'useful_life' => [
                    'nullable',
                    'integer',
                    'min:1'
                ],
                'residual_value' => [
                    'nullable',
                    'numeric',
                    'min:0'
                ],
                'depreciation_start_date' => [
                    'nullable',
                    'date'
                ],
                'warranty_start' => [
                    'nullable',
                    'date'
                ],
                'warranty_end' => [
                    'nullable',
                    'date',
                    'after_or_equal:warranty_start'
                ],
                'warranty_note' => [
                    'nullable',
                    'string'
                ],

                /*
                |--------------------------------------------------------------------------
                | MAINTENANCE
                |--------------------------------------------------------------------------
                */

                'maintenance_required' => [
                    'required',
                    'boolean'
                ],

                'maintenance_type' => [
                    'required_if:maintenance_required,1',
                    'nullable',
                    'string',
                    'in:preventive,corrective',
                ],

                'maintenance_trigger' => [
                    'required_if:maintenance_required,1',
                    'nullable',
                    'string',
                    'in:calendar',
                ],

                'maintenance_interval' => [
                    'required_if:maintenance_required,1',
                    'nullable',
                    'integer',
                    'min:1'
                ],

                'maintenance_interval_unit' => [
                    'required_if:maintenance_required,1',
                    'nullable',
                    'in:day,week,month,year'
                ],

                'maintenance_start_date' => [
                    'required_if:maintenance_required,1',
                    'nullable',
                    'date'
                ],

                'last_maintenance_date' => [
                    'nullable',
                    'date'
                ],

                'next_maintenance_date' => [
                    'nullable',
                    'date'
                ],

                'location' => [
                    'nullable',
                    'string',
                    'max:255'
                ],

                'status' => [
                    'required',
                    'integer',
                    'in:0,1'
                ],

                'asset_photos' => [
                    'nullable',
                    'array',
                    'max:3'
                ],

                'asset_photos.*' => [
                    'file',
                    'max:5120',
                    'mimes:jpg,jpeg,png'
                ],

                'invoice_documents' => [
                    'nullable',
                    'array',
                    'max:5'
                ],

                'invoice_documents.*' => [
                    'file',
                    'max:10240',
                    'mimes:pdf,jpg,jpeg,png'
                ],
            ],
            [
                'asset_name.required' =>
                    'Nama asset wajib diisi.',

                'category_id.required' =>
                    'Kategori wajib dipilih atau diisi.',

                'vendor_id.required' =>
                    'Vendor wajib dipilih.',

                'vendor_id.exists' =>
                    'Vendor tidak valid.',

                'responsible_user_id.exists' =>
                    'Responsible user tidak valid.',

                'purchase_price.numeric' =>
                    'Harga pembelian harus berupa angka.',

                'purchase_price.min' =>
                    'Harga pembelian tidak boleh kurang dari 0.',

                'useful_life.integer' =>
                    'Umur manfaat harus berupa angka.',

                'useful_life.min' =>
                    'Umur manfaat minimal 1 tahun.',

                'warranty_end.after_or_equal' =>
                    'Tanggal akhir warranty tidak boleh sebelum tanggal mulai.',

                'maintenance_type.required_if' =>
                    'Jenis maintenance wajib dipilih jika maintenance aktif.',

                'maintenance_trigger.required_if' =>
                    'Trigger maintenance wajib dipilih jika maintenance aktif.',

                'maintenance_interval.required_if' =>
                    'Interval maintenance wajib diisi jika maintenance aktif.',

                'maintenance_interval_unit.required_if' =>
                    'Satuan interval maintenance wajib dipilih jika maintenance aktif.',

                'maintenance_start_date.required_if' =>
                    'Tanggal mulai maintenance wajib diisi jika maintenance aktif.',

                'status.required' =>
                    'Status wajib dipilih.',

                'asset_photos.max' =>
                    'Foto asset maksimal 3 file.',

                'asset_photos.*.file' =>
                    'File foto tidak valid.',

                'asset_photos.*.max' =>
                    'Ukuran setiap foto maksimal 5 MB.',

                'asset_photos.*.mimes' =>
                    'Foto hanya boleh JPG, JPEG atau PNG.',

                'invoice_documents.max' =>
                    'Dokumen maksimal 5 file.',

                'invoice_documents.*.file' =>
                    'File dokumen tidak valid.',

                'invoice_documents.*.max' =>
                    'Ukuran setiap dokumen maksimal 10 MB.',

                'invoice_documents.*.mimes' =>
                    'Dokumen hanya boleh PDF, JPG, JPEG atau PNG.',
            ]
        );

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        $uploadedImagePaths = [];
        $uploadedInvoicePaths = [];

        try {

            /*
            |--------------------------------------------------------------------------
            | CATEGORY
            |--------------------------------------------------------------------------
            */

            $categoryValue = $request->category_id;

            if (is_numeric($categoryValue)) {

                $category = Category::where(
                    'company_id',
                    $companyId
                )
                    ->where(
                        'id',
                        $categoryValue
                    )
                    ->first();

                if (!$category) {

                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Kategori tidak valid.'
                    ], 422);
                }

            } else {

                if (
                    !is_string($categoryValue) ||
                    !str_starts_with($categoryValue, 'new:')
                ) {

                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Kategori tidak valid.'
                    ], 422);
                }

                $categoryName = trim(
                    substr($categoryValue, 4)
                );

                if ($categoryName === '') {

                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' =>
                            'Nama kategori wajib diisi.'
                    ], 422);
                }

                $category = Category::where(
                    'company_id',
                    $companyId
                )
                    ->whereRaw(
                        'LOWER(category_name) = ?',
                        [
                            strtolower($categoryName)
                        ]
                    )
                    ->first();

                if (!$category) {

                    $categoryCode =
                        CodeHelper::generateNumber(
                            'CAT-',
                            Category::class,
                            'category_code',
                            $companyId
                        );

                    $category = Category::create([

                        'company_id' =>
                            $companyId,

                        'category_code' =>
                            $categoryCode,

                        'category_name' =>
                            strtoupper($categoryName),

                        'description' =>
                            null,

                        'status' =>
                            1,
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | SUB CATEGORY
            |--------------------------------------------------------------------------
            */

            $subCategoryId = null;

            if ($request->filled('sub_category_id')) {

                $subCategoryValue =
                    $request->sub_category_id;

                if (is_numeric($subCategoryValue)) {

                    $subCategory =
                        SubCategory::where(
                            'company_id',
                            $companyId
                        )
                            ->where(
                                'category_id',
                                $category->id
                            )
                            ->where(
                                'id',
                                $subCategoryValue
                            )
                            ->first();

                    if (!$subCategory) {

                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' =>
                                'Sub kategori tidak valid.'
                        ], 422);
                    }

                    $subCategoryId =
                        $subCategory->id;

                } else {

                    if (
                        !is_string($subCategoryValue) ||
                        !str_starts_with(
                            $subCategoryValue,
                            'new:'
                        )
                    ) {

                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' =>
                                'Sub kategori tidak valid.'
                        ], 422);
                    }

                    $subCategoryName =
                        trim(
                            substr(
                                $subCategoryValue,
                                4
                            )
                        );

                    if ($subCategoryName === '') {

                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' =>
                                'Nama sub kategori wajib diisi.'
                        ], 422);
                    }

                    $subCategory =
                        SubCategory::where(
                            'company_id',
                            $companyId
                        )
                            ->where(
                                'category_id',
                                $category->id
                            )
                            ->whereRaw(
                                'LOWER(sub_category_name) = ?',
                                [
                                    strtolower(
                                        $subCategoryName
                                    )
                                ]
                            )
                            ->first();

                    if (!$subCategory) {

                        $subCategoryCode =
                            CodeHelper::generateNumber(
                                'SUBCAT-',
                                SubCategory::class,
                                'sub_category_code',
                                $companyId
                            );

                        $subCategory =
                            SubCategory::create([

                                'company_id' =>
                                    $companyId,

                                'category_id' =>
                                    $category->id,

                                'sub_category_code' =>
                                    $subCategoryCode,

                                'sub_category_name' =>
                                    strtoupper(
                                        $subCategoryName
                                    ),

                                'description' =>
                                    null,

                                'status' =>
                                    1,
                            ]);
                    }

                    $subCategoryId =
                        $subCategory->id;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | VENDOR
            |--------------------------------------------------------------------------
            */

            $vendorExists =
                Vendor::where(
                    'company_id',
                    $companyId
                )
                    ->where(
                        'id',
                        $request->vendor_id
                    )
                    ->exists();

            if (!$vendorExists) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Vendor tidak valid.'
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | RESPONSIBLE USER
            |--------------------------------------------------------------------------
            */

            if ($request->filled('responsible_user_id')) {

                $userExists =
                    User::where(
                        'company_id',
                        $companyId
                    )
                        ->where(
                            'id',
                            $request->responsible_user_id
                        )
                        ->exists();

                if (!$userExists) {

                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' =>
                            'Responsible user tidak valid.'
                    ], 422);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | ASSET CODE
            |--------------------------------------------------------------------------
            */

            $assetCode =
                CodeHelper::generateNumber(
                    'AST-',
                    Asset::class,
                    'asset_code',
                    $companyId
                );


            /*
            |--------------------------------------------------------------------------
            | QR TOKEN
            |--------------------------------------------------------------------------
            */

            $qrToken =
                Str::uuid()->toString();


            /*
            |--------------------------------------------------------------------------
            | MAINTENANCE NEXT DATE
            |--------------------------------------------------------------------------
            |
            | Jika user mengisi next_maintenance_date,
            | tanggal tersebut menjadi sumber utama.
            |
            | Jika kosong, baru dihitung otomatis.
            |
            */

            $maintenanceRequired =
                $request->boolean('maintenance_required');

            $nextMaintenanceDate = null;

            if ($maintenanceRequired) {

                if (
                    $request->filled(
                        'next_maintenance_date'
                    )
                ) {

                    $nextMaintenanceDate =
                        Carbon::parse(
                            $request->next_maintenance_date
                        );

                } else {

                    $nextMaintenanceDate =
                        $this->calculateNextMaintenanceDate(
                            $request
                        );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | CREATE ASSET
            |--------------------------------------------------------------------------
            */

            $asset = Asset::create([

                'company_id' =>
                    $companyId,

                'category_id' =>
                    $category->id,

                'sub_category_id' =>
                    $subCategoryId,

                'vendor_id' =>
                    $request->vendor_id,

                'responsible_user_id' =>
                    $request->responsible_user_id,

                'asset_code' =>
                    $assetCode,

                'asset_name' =>
                    strtoupper(
                        trim(
                            $request->asset_name
                        )
                    ),

                'asset_condition' =>
                    $request->asset_condition,

                'brand' =>
                    $request->brand,

                'model' =>
                    $request->model,

                'serial_number' =>
                    $request->serial_number,

                'description' =>
                    $request->description,

                'purchase_date' =>
                    $request->purchase_date,

                'purchase_price' =>
                    $request->purchase_price,

                'purchase_invoice' =>
                    $request->purchase_invoice,

                'depreciation_method' =>
                    $request->depreciation_method,

                'useful_life' =>
                    $request->useful_life,

                'residual_value' =>
                    $request->residual_value ?? 0,

                'depreciation_start_date' =>
                    $request->depreciation_start_date,

                'warranty_start' =>
                    $request->warranty_start,

                'warranty_end' =>
                    $request->warranty_end,

                'warranty_note' =>
                    $request->warranty_note,

                'maintenance_required' =>
                    $maintenanceRequired,

                'maintenance_type' =>
                    $maintenanceRequired
                        ? $request->maintenance_type
                        : null,

                'maintenance_trigger' =>
                    $maintenanceRequired
                        ? $request->maintenance_trigger
                        : null,

                'maintenance_interval' =>
                    $maintenanceRequired
                        ? $request->maintenance_interval
                        : null,

                'maintenance_interval_unit' =>
                    $maintenanceRequired
                        ? $request->maintenance_interval_unit
                        : null,

                'maintenance_start_date' =>
                    $maintenanceRequired
                        ? $request->maintenance_start_date
                        : null,

                'last_maintenance_date' =>
                    $maintenanceRequired
                        ? $request->last_maintenance_date
                        : null,

                'next_maintenance_date' =>
                    $maintenanceRequired
                        ? $nextMaintenanceDate
                        : null,

                'location' =>
                    $request->location,

                'status' =>
                    $request->status,

                'qr_token' =>
                    $qrToken,

                'qr_generated_at' =>
                    now(),
            ]);


            /*
            |--------------------------------------------------------------------------
            | CREATE MAINTENANCE SCHEDULE
            |--------------------------------------------------------------------------
            */

            if (
                $maintenanceRequired &&
                $nextMaintenanceDate
            ) {

                $this->syncMaintenanceSchedule(
                    $asset,
                    $companyId,
                    true,
                    $nextMaintenanceDate
                );
            }


            /*
            |--------------------------------------------------------------------------
            | UPLOAD PHOTOS
            |--------------------------------------------------------------------------
            */

            if ($request->hasFile('asset_photos')) {

                $companyCode = $asset->company->company_code;
                $datePath = now()->format('Y/m/d');

                foreach (
                    $request->file('asset_photos')
                    as $index => $file
                ) {

                    if (!$file->isValid()) {

                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' =>
                                'Salah satu foto gagal diupload: ' .
                                $file->getErrorMessage()
                        ], 422);
                    }

                    $extension =
                        strtolower(
                            $file->getClientOriginalExtension()
                        );

                    $filename =
                        $assetCode . '-' .
                        Str::uuid() .
                        '.' .
                        $extension;

                    $destinationPath =
                        storage_path(
                            'app/public/documents/' .
                            $companyCode .
                            '/assets/img/' .
                            $datePath
                        );

                    if (!is_dir($destinationPath)) {

                        mkdir(
                            $destinationPath,
                            0755,
                            true
                        );
                    }

                    $file->move(
                        $destinationPath,
                        $filename
                    );

                    $path =
                        'documents/' .
                        $companyCode .
                        '/assets/img/' .
                        $datePath . '/' .
                        $filename;

                    $uploadedImagePaths[] =
                        $path;

                    AssetPhoto::create([

                        'asset_id' =>
                            $asset->id,

                        'file_path' =>
                            $path,

                        'original_name' =>
                            $file->getClientOriginalName(),

                        'sort_order' =>
                            $index,
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | UPLOAD INVOICE DOCUMENTS
            |--------------------------------------------------------------------------
            */

            if ($request->hasFile('invoice_documents')) {

                $companyCode = $asset->company->company_code;
                $datePath = now()->format('Y/m/d');

                foreach (
                    $request->file('invoice_documents')
                    as $file
                ) {

                    if (!$file->isValid()) {

                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' =>
                                'Salah satu dokumen gagal diupload: ' .
                                $file->getErrorMessage()
                        ], 422);
                    }

                    $extension =
                        strtolower(
                            $file->getClientOriginalExtension()
                        );

                    $filename =
                        $assetCode . '-' .
                        Str::uuid() .
                        '.' .
                        $extension;

                    $destinationPath =
                        storage_path(
                            'app/public/documents/' .
                            $companyCode .
                            '/assets/inv/' .
                            $datePath
                        );

                    if (!is_dir($destinationPath)) {

                        mkdir(
                            $destinationPath,
                            0755,
                            true
                        );
                    }

                    $file->move(
                        $destinationPath,
                        $filename
                    );

                    $path =
                        'documents/' .
                        $companyCode .
                        '/assets/inv/' .
                        $datePath . '/' .
                        $filename;

                    $uploadedInvoicePaths[] =
                        $path;

                    AssetDocument::create([

                        'asset_id' =>
                            $asset->id,

                        'document_type' =>
                            'invoice',

                        'file_path' =>
                            $path,

                        'original_name' =>
                            $file->getClientOriginalName(),
                    ]);
                }
            }


            DB::commit();

            $asset->load([
                'photos',
                'documents'
            ]);

            return response()->json([
                'success' => true,
                'message' =>
                    'Asset berhasil ditambahkan.',
                'data' =>
                    $asset
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            foreach ($uploadedImagePaths as $path) {

                $fullPath =
                    storage_path(
                        'app/public/' . $path
                    );

                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }

            foreach ($uploadedInvoicePaths as $path) {

                $fullPath =
                    storage_path(
                        'app/public/' . $path
                    );

                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }

            Log::error(
                'Gagal menambahkan asset',
                [
                    'message' =>
                        $e->getMessage(),

                    'file' =>
                        $e->getFile(),

                    'line' =>
                        $e->getLine(),

                    'trace' =>
                        $e->getTraceAsString(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'Gagal menambahkan asset.',
                'error' =>
                    $e->getMessage()
            ], 500);
        }
    }


    /**
     * =========================================================================
     * EDIT ASSET
     * =========================================================================
     */
    public function edit($id)
    {
        $companyId = Auth::user()->company_id;

        $asset = Asset::where(
            'company_id',
            $companyId
        )
            ->with([
                'photos',
                'documents',
            ])
            ->findOrFail($id);

        $categories = Category::where(
            'company_id',
            $companyId
        )
            ->where('status', 1)
            ->orderBy('category_name')
            ->get();

        $subCategories = SubCategory::where(
            'company_id',
            $companyId
        )
            ->where('status', 1)
            ->orderBy('sub_category_name')
            ->get();

        $vendors = Vendor::where(
            'company_id',
            $companyId
        )
            ->where('status', 1)
            ->orderBy('vendor_name')
            ->get();

        $users = User::where(
            'company_id',
            $companyId
        )
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return view(
            'dashboard.asset.edit',
            compact(
                'asset',
                'categories',
                'subCategories',
                'vendors',
                'users'
            )
        );
    }


    /**
     * =========================================================================
     * UPDATE ASSET
     * =========================================================================
     */
    public function update(Request $request)
    {
        $companyId = Auth::user()->company_id;

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validator = Validator::make(
            $request->all(),
            [
                'id' => [
                    'required',
                    'integer'
                ],

                'asset_name' => [
                    'required',
                    'string',
                    'max:255'
                ],

                'category_id' => [
                    'required'
                ],

                'sub_category_id' => [
                    'nullable'
                ],

                'vendor_id' => [
                    'required',
                    'exists:vendors,id'
                ],

                'responsible_user_id' => [
                    'nullable',
                    'exists:users,id'
                ],

                'brand' => [
                    'nullable',
                    'string',
                    'max:100'
                ],

                'model' => [
                    'nullable',
                    'string',
                    'max:150'
                ],

                'serial_number' => [
                    'nullable',
                    'string',
                    'max:150'
                ],

                'description' => [
                    'nullable',
                    'string'
                ],

                'purchase_date' => [
                    'nullable',
                    'date'
                ],

                'purchase_price' => [
                    'nullable',
                    'numeric',
                    'min:0'
                ],

                'purchase_invoice' => [
                    'nullable',
                    'string',
                    'max:100'
                ],

                'depreciation_method' => [
                    'nullable',
                    'string',
                    'max:50'
                ],

                'useful_life' => [
                    'nullable',
                    'integer',
                    'min:1'
                ],

                'residual_value' => [
                    'nullable',
                    'numeric',
                    'min:0'
                ],

                'depreciation_start_date' => [
                    'nullable',
                    'date'
                ],

                'warranty_start' => [
                    'nullable',
                    'date'
                ],

                'warranty_end' => [
                    'nullable',
                    'date',
                    'after_or_equal:warranty_start'
                ],

                'warranty_note' => [
                    'nullable',
                    'string'
                ],

                /*
                |--------------------------------------------------------------------------
                | MAINTENANCE
                |--------------------------------------------------------------------------
                */

                'maintenance_required' => [
                    'required',
                    'boolean',
                ],

                'maintenance_type' => [
                    'required_if:maintenance_required,1',
                    'nullable',
                    'string',
                    'in:preventive,corrective',
                ],

                'maintenance_trigger' => [
                    'required_if:maintenance_required,1',
                    'nullable',
                    'string',
                    'in:calendar',
                ],

                'maintenance_interval' => [
                    'required_if:maintenance_required,1',
                    'nullable',
                    'integer',
                    'min:1',
                ],

                'maintenance_interval_unit' => [
                    'required_if:maintenance_required,1',
                    'nullable',
                    'string',
                    'in:day,week,month,year',
                ],

                'maintenance_start_date' => [
                    'required_if:maintenance_required,1',
                    'nullable',
                    'date',
                ],

                'last_maintenance_date' => [
                    'nullable',
                    'date',
                ],

                'next_maintenance_date' => [
                    'nullable',
                    'date',
                ],

                'location' => [
                    'nullable',
                    'string',
                    'max:255'
                ],

                'status' => [
                    'required',
                    'integer',
                    'in:0,1'
                ],

                'delete_images' => [
                    'nullable',
                    'array'
                ],

                'delete_images.*' => [
                    'integer'
                ],

                'asset_photos' => [
                    'nullable',
                    'array'
                ],

                'asset_photos.*' => [
                    'file',
                    'max:5120',
                    'mimes:jpg,jpeg,png'
                ],

                'delete_invoice_documents' => [
                    'nullable',
                    'array'
                ],

                'delete_invoice_documents.*' => [
                    'integer'
                ],

                'invoice_documents' => [
                    'nullable',
                    'array'
                ],

                'invoice_documents.*' => [
                    'file',
                    'max:10240',
                    'mimes:pdf,jpg,jpeg,png'
                ],
            ],
            [
                'id.required' =>
                    'ID asset wajib dikirim.',

                'id.integer' =>
                    'ID asset tidak valid.',

                'asset_name.required' =>
                    'Nama asset wajib diisi.',

                'category_id.required' =>
                    'Kategori wajib dipilih atau diisi.',

                'vendor_id.required' =>
                    'Vendor wajib dipilih.',

                'vendor_id.exists' =>
                    'Vendor tidak valid.',

                'responsible_user_id.exists' =>
                    'Responsible user tidak valid.',

                'purchase_price.numeric' =>
                    'Harga pembelian harus berupa angka.',

                'purchase_price.min' =>
                    'Harga pembelian tidak boleh kurang dari 0.',

                'useful_life.integer' =>
                    'Umur manfaat harus berupa angka.',

                'useful_life.min' =>
                    'Umur manfaat minimal 1 tahun.',

                'warranty_end.after_or_equal' =>
                    'Tanggal akhir warranty tidak boleh sebelum tanggal mulai.',

                'maintenance_type.required_if' =>
                    'Jenis maintenance wajib dipilih jika maintenance aktif.',

                'maintenance_trigger.required_if' =>
                    'Trigger maintenance wajib dipilih jika maintenance aktif.',

                'maintenance_interval.required_if' =>
                    'Interval maintenance wajib diisi jika maintenance aktif.',

                'maintenance_interval_unit.required_if' =>
                    'Satuan interval maintenance wajib dipilih jika maintenance aktif.',

                'maintenance_start_date.required_if' =>
                    'Tanggal mulai maintenance wajib diisi jika maintenance aktif.',

                'status.required' =>
                    'Status wajib dipilih.',

                'delete_images.*.integer' =>
                    'ID foto tidak valid.',

                'asset_photos.*.file' =>
                    'File foto tidak valid.',

                'asset_photos.*.max' =>
                    'Ukuran setiap foto maksimal 5 MB.',

                'asset_photos.*.mimes' =>
                    'Foto hanya boleh JPG, JPEG atau PNG.',

                'delete_invoice_documents.*.integer' =>
                    'ID dokumen tidak valid.',

                'invoice_documents.*.file' =>
                    'File invoice tidak valid.',

                'invoice_documents.*.max' =>
                    'Ukuran setiap invoice maksimal 10 MB.',

                'invoice_documents.*.mimes' =>
                    'Invoice hanya boleh PDF, JPG, JPEG atau PNG.',
            ]
        );

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | FIND ASSET
        |--------------------------------------------------------------------------
        */

        $asset = Asset::where(
            'company_id',
            $companyId
        )
            ->where(
                'id',
                $request->id
            )
            ->first();

        if (!$asset) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Asset tidak ditemukan.'
            ], 404);
        }


        DB::beginTransaction();

        $uploadedImagePaths = [];
        $uploadedInvoicePaths = [];

        try {

            /*
            |--------------------------------------------------------------------------
            | CATEGORY
            |--------------------------------------------------------------------------
            */

            $categoryValue =
                $request->category_id;

            if (is_numeric($categoryValue)) {

                $category =
                    Category::where(
                        'company_id',
                        $companyId
                    )
                        ->where(
                            'id',
                            $categoryValue
                        )
                        ->first();

                if (!$category) {

                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' =>
                            'Kategori tidak valid.'
                    ], 422);
                }

            } else {

                if (
                    !is_string($categoryValue) ||
                    !str_starts_with(
                        $categoryValue,
                        'new:'
                    )
                ) {

                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' =>
                            'Kategori tidak valid.'
                    ], 422);
                }

                $categoryName =
                    trim(
                        substr(
                            $categoryValue,
                            4
                        )
                    );

                if ($categoryName === '') {

                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' =>
                            'Nama kategori wajib diisi.'
                    ], 422);
                }

                $category =
                    Category::where(
                        'company_id',
                        $companyId
                    )
                        ->whereRaw(
                            'LOWER(category_name) = ?',
                            [
                                strtolower(
                                    $categoryName
                                )
                            ]
                        )
                        ->first();

                if (!$category) {

                    $categoryCode =
                        CodeHelper::generateNumber(
                            'CAT-',
                            Category::class,
                            'category_code',
                            $companyId
                        );

                    $category =
                        Category::create([
                            'company_id' =>
                                $companyId,

                            'category_code' =>
                                $categoryCode,

                            'category_name' =>
                                strtoupper(
                                    $categoryName
                                ),

                            'description' =>
                                null,

                            'status' =>
                                1,
                        ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | SUB CATEGORY
            |--------------------------------------------------------------------------
            */

            $subCategoryId = null;

            if ($request->filled('sub_category_id')) {

                $subCategoryValue =
                    $request->sub_category_id;

                if (is_numeric($subCategoryValue)) {

                    $subCategory =
                        SubCategory::where(
                            'company_id',
                            $companyId
                        )
                            ->where(
                                'category_id',
                                $category->id
                            )
                            ->where(
                                'id',
                                $subCategoryValue
                            )
                            ->first();

                    if (!$subCategory) {

                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' =>
                                'Sub kategori tidak valid.'
                        ], 422);
                    }

                    $subCategoryId =
                        $subCategory->id;

                } else {

                    if (
                        !is_string($subCategoryValue) ||
                        !str_starts_with(
                            $subCategoryValue,
                            'new:'
                        )
                    ) {

                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' =>
                                'Sub kategori tidak valid.'
                        ], 422);
                    }

                    $subCategoryName =
                        trim(
                            substr(
                                $subCategoryValue,
                                4
                            )
                        );

                    if ($subCategoryName === '') {

                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' =>
                                'Nama sub kategori wajib diisi.'
                        ], 422);
                    }

                    $subCategory =
                        SubCategory::where(
                            'company_id',
                            $companyId
                        )
                            ->where(
                                'category_id',
                                $category->id
                            )
                            ->whereRaw(
                                'LOWER(sub_category_name) = ?',
                                [
                                    strtolower(
                                        $subCategoryName
                                    )
                                ]
                            )
                            ->first();

                    if (!$subCategory) {

                        $subCategoryCode =
                            CodeHelper::generateNumber(
                                'SUBCAT-',
                                SubCategory::class,
                                'sub_category_code',
                                $companyId
                            );

                        $subCategory =
                            SubCategory::create([
                                'company_id' =>
                                    $companyId,

                                'category_id' =>
                                    $category->id,

                                'sub_category_code' =>
                                    $subCategoryCode,

                                'sub_category_name' =>
                                    strtoupper(
                                        $subCategoryName
                                    ),

                                'description' =>
                                    null,

                                'status' =>
                                    1,
                            ]);
                    }

                    $subCategoryId =
                        $subCategory->id;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | VENDOR
            |--------------------------------------------------------------------------
            */

            $vendorExists =
                Vendor::where(
                    'company_id',
                    $companyId
                )
                    ->where(
                        'id',
                        $request->vendor_id
                    )
                    ->exists();

            if (!$vendorExists) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Vendor tidak valid.'
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | RESPONSIBLE USER
            |--------------------------------------------------------------------------
            */

            if ($request->filled('responsible_user_id')) {

                $userExists =
                    User::where(
                        'company_id',
                        $companyId
                    )
                        ->where(
                            'id',
                            $request->responsible_user_id
                        )
                        ->exists();

                if (!$userExists) {

                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' =>
                            'Responsible user tidak valid.'
                    ], 422);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | DELETE EXISTING PHOTOS
            |--------------------------------------------------------------------------
            */

            $deleteImages =
                $request->input(
                    'delete_images',
                    []
                );

            if (!is_array($deleteImages)) {
                $deleteImages = [];
            }

            $deleteImages =
                array_values(
                    array_unique(
                        array_filter(
                            $deleteImages,
                            function ($id) {
                                return is_numeric($id);
                            }
                        )
                    )
                );

            if (count($deleteImages) > 0) {

                $photosToDelete =
                    AssetPhoto::where(
                        'asset_id',
                        $asset->id
                    )
                        ->whereIn(
                            'id',
                            $deleteImages
                        )
                        ->get();

                foreach (
                    $photosToDelete
                    as $photo
                ) {

                    $fullPath =
                        storage_path(
                            'app/public/' . $photo->file_path
                        );

                    if (
                        file_exists(
                            $fullPath
                        )
                    ) {
                        unlink($fullPath);
                    }

                    $photo->delete();
                }
            }


            /*
            |--------------------------------------------------------------------------
            | CHECK PHOTO COUNT
            |--------------------------------------------------------------------------
            */

            $remainingPhotoCount =
                AssetPhoto::where(
                    'asset_id',
                    $asset->id
                )
                    ->count();

            $newPhotoFiles =
                $request->file(
                    'asset_photos',
                    []
                );

            if (!is_array($newPhotoFiles)) {

                $newPhotoFiles =
                    $newPhotoFiles
                        ? [$newPhotoFiles]
                        : [];
            }

            $newPhotoFiles =
                array_values(
                    array_filter(
                        $newPhotoFiles
                    )
                );

            $newPhotoCount =
                count(
                    $newPhotoFiles
                );

            if (
                $remainingPhotoCount +
                $newPhotoCount > 3
            ) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Total foto asset maksimal 3 file.'
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | UPLOAD NEW PHOTOS
            |--------------------------------------------------------------------------
            */

            if ($newPhotoCount > 0) {

                $lastSortOrder =
                    AssetPhoto::where(
                        'asset_id',
                        $asset->id
                    )
                        ->max(
                            'sort_order'
                        );

                $lastSortOrder =
                    $lastSortOrder ?? -1;

                $companyCode =
                    $asset->company->company_code;

                $datePath =
                    now()->format('Y/m/d');

                foreach (
                    $newPhotoFiles
                    as $file
                ) {

                    if (!$file->isValid()) {

                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' =>
                                'Salah satu foto gagal diupload: ' .
                                $file->getErrorMessage()
                        ], 422);
                    }

                    $extension =
                        strtolower(
                            $file->getClientOriginalExtension()
                        );

                    $filename =
                        $asset->asset_code .
                        '-' .
                        Str::uuid() .
                        '.' .
                        $extension;

                    $destinationPath =
                        storage_path(
                            'app/public/documents/' .
                            $companyCode .
                            '/assets/img/' .
                            $datePath
                        );

                    if (!is_dir($destinationPath)) {

                        mkdir(
                            $destinationPath,
                            0755,
                            true
                        );
                    }

                    $file->move(
                        $destinationPath,
                        $filename
                    );

                    $path =
                        'documents/' .
                        $companyCode .
                        '/assets/img/' .
                        $datePath .
                        '/' .
                        $filename;

                    $uploadedImagePaths[] =
                        $path;

                    $lastSortOrder++;

                    AssetPhoto::create([
                        'asset_id' =>
                            $asset->id,

                        'file_path' =>
                            $path,

                        'original_name' =>
                            $file->getClientOriginalName(),

                        'sort_order' =>
                            $lastSortOrder,
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | DELETE EXISTING INVOICE DOCUMENTS
            |--------------------------------------------------------------------------
            */

            $deleteInvoices =
                $request->input(
                    'delete_invoice_documents',
                    []
                );

            if (!is_array($deleteInvoices)) {
                $deleteInvoices = [];
            }

            $deleteInvoices =
                array_values(
                    array_unique(
                        array_filter(
                            $deleteInvoices,
                            function ($id) {
                                return is_numeric($id);
                            }
                        )
                    )
                );

            if (count($deleteInvoices) > 0) {

                $documentsToDelete =
                    AssetDocument::where(
                        'asset_id',
                        $asset->id
                    )
                        ->where(
                            'document_type',
                            'invoice'
                        )
                        ->whereIn(
                            'id',
                            $deleteInvoices
                        )
                        ->get();

                foreach (
                    $documentsToDelete
                    as $document
                ) {

                    $fullPath =
                        storage_path(
                            'app/public/' . $document->file_path
                        );

                    if (
                        file_exists(
                            $fullPath
                        )
                    ) {
                        unlink($fullPath);
                    }

                    $document->delete();
                }
            }


            /*
            |--------------------------------------------------------------------------
            | CHECK DOCUMENT COUNT
            |--------------------------------------------------------------------------
            */

            $remainingDocumentCount =
                AssetDocument::where(
                    'asset_id',
                    $asset->id
                )
                    ->where(
                        'document_type',
                        'invoice'
                    )
                    ->count();

            $newInvoiceFiles =
                $request->file(
                    'invoice_documents',
                    []
                );

            if (!is_array($newInvoiceFiles)) {

                $newInvoiceFiles =
                    $newInvoiceFiles
                        ? [$newInvoiceFiles]
                        : [];
            }

            $newInvoiceFiles =
                array_values(
                    array_filter(
                        $newInvoiceFiles
                    )
                );

            $newInvoiceCount =
                count(
                    $newInvoiceFiles
                );

            if (
                $remainingDocumentCount +
                $newInvoiceCount > 5
            ) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Total dokumen invoice maksimal 5 file.'
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | UPLOAD NEW DOCUMENTS
            |--------------------------------------------------------------------------
            */

            if ($newInvoiceCount > 0) {

                $companyCode =
                    $asset->company->company_code;

                $datePath =
                    now()->format('Y/m/d');

                foreach (
                    $newInvoiceFiles
                    as $file
                ) {

                    if (!$file->isValid()) {

                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' =>
                                'Salah satu invoice gagal diupload: ' .
                                $file->getErrorMessage()
                        ], 422);
                    }

                    $extension =
                        strtolower(
                            $file->getClientOriginalExtension()
                        );

                    $filename =
                        $asset->asset_code .
                        '-' .
                        Str::uuid() .
                        '.' .
                        $extension;

                    $destinationPath =
                        storage_path(
                            'app/public/documents/' .
                            $companyCode .
                            '/assets/inv/' .
                            $datePath
                        );

                    if (!is_dir($destinationPath)) {

                        mkdir(
                            $destinationPath,
                            0755,
                            true
                        );
                    }

                    $file->move(
                        $destinationPath,
                        $filename
                    );

                    $path =
                        'documents/' .
                        $companyCode .
                        '/assets/inv/' .
                        $datePath .
                        '/' .
                        $filename;

                    $uploadedInvoicePaths[] =
                        $path;

                    AssetDocument::create([
                        'asset_id' =>
                            $asset->id,

                        'document_type' =>
                            'invoice',

                        'file_path' =>
                            $path,

                        'original_name' =>
                            $file->getClientOriginalName(),
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | MAINTENANCE CALCULATION
            |--------------------------------------------------------------------------
            |
            | PRIORITAS:
            |
            | 1. next_maintenance_date dari form
            | 2. jika kosong -> hitung otomatis
            |
            */

            $maintenanceRequired =
                $request->boolean('maintenance_required');

            $nextMaintenanceDate = null;

            if ($maintenanceRequired) {

                if (
                    $request->filled(
                        'next_maintenance_date'
                    )
                ) {

                    $nextMaintenanceDate =
                        Carbon::parse(
                            $request->next_maintenance_date
                        );

                } else {

                    $nextMaintenanceDate =
                        $this->calculateNextMaintenanceDate(
                            $request
                        );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE ASSET
            |--------------------------------------------------------------------------
            */

            $asset->update([

                'category_id' =>
                    $category->id,

                'sub_category_id' =>
                    $subCategoryId,

                'asset_name' =>
                    strtoupper(
                        trim(
                            $request->asset_name
                        )
                    ),

                'vendor_id' =>
                    $request->vendor_id,

                'responsible_user_id' =>
                    $request->responsible_user_id,

                'brand' =>
                    $request->brand,

                'model' =>
                    $request->model,

                'serial_number' =>
                    $request->serial_number,

                'description' =>
                    $request->description,

                'purchase_date' =>
                    $request->purchase_date,

                'purchase_price' =>
                    $request->purchase_price,

                'purchase_invoice' =>
                    $request->purchase_invoice,

                'depreciation_method' =>
                    $request->depreciation_method,

                'useful_life' =>
                    $request->useful_life,

                'residual_value' =>
                    $request->residual_value ?? 0,

                'depreciation_start_date' =>
                    $request->depreciation_start_date,

                'warranty_start' =>
                    $request->warranty_start,

                'warranty_end' =>
                    $request->warranty_end,

                'warranty_note' =>
                    $request->warranty_note,

                'location' =>
                    $request->location,

                'status' =>
                    $request->status,

                'maintenance_required' =>
                    $maintenanceRequired,

                'maintenance_type' =>
                    $maintenanceRequired
                        ? $request->maintenance_type
                        : null,

                'maintenance_trigger' =>
                    $maintenanceRequired
                        ? $request->maintenance_trigger
                        : null,

                'maintenance_interval' =>
                    $maintenanceRequired
                        ? $request->maintenance_interval
                        : null,

                'maintenance_interval_unit' =>
                    $maintenanceRequired
                        ? $request->maintenance_interval_unit
                        : null,

                'maintenance_start_date' =>
                    $maintenanceRequired
                        ? $request->maintenance_start_date
                        : null,

                'last_maintenance_date' =>
                    $maintenanceRequired
                        ? $request->last_maintenance_date
                        : null,

                'next_maintenance_date' =>
                    $maintenanceRequired
                        ? $nextMaintenanceDate
                        : null,
            ]);


            /*
            |--------------------------------------------------------------------------
            | SYNC MAINTENANCE
            |--------------------------------------------------------------------------
            */

            $this->syncMaintenanceSchedule(
                $asset,
                $companyId,
                $maintenanceRequired,
                $nextMaintenanceDate
            );


            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            DB::commit();


            /*
            |--------------------------------------------------------------------------
            | REFRESH DATA
            |--------------------------------------------------------------------------
            */

            $asset->refresh();

            $asset->load([
                'photos',
                'documents'
            ]);

            return response()->json([
                'success' =>
                    true,

                'message' =>
                    'Asset berhasil diperbarui.',

                'data' =>
                    $asset
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();


            /*
            |--------------------------------------------------------------------------
            | CLEANUP NEW PHOTOS
            |--------------------------------------------------------------------------
            */

            foreach (
                $uploadedImagePaths
                as $path
            ) {

                $fullPath =
                    storage_path(
                        'app/public/' . $path
                    );

                if (
                    file_exists(
                        $fullPath
                    )
                ) {

                    unlink($fullPath);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | CLEANUP NEW DOCUMENTS
            |--------------------------------------------------------------------------
            */

            foreach (
                $uploadedInvoicePaths
                as $path
            ) {

                $fullPath =
                    storage_path(
                        'app/public/' . $path
                    );

                if (
                    file_exists(
                        $fullPath
                    )
                ) {

                    unlink($fullPath);
                }
            }


            Log::error(
                'Gagal memperbarui asset',
                [

                    'message' =>
                        $e->getMessage(),

                    'file' =>
                        $e->getFile(),

                    'line' =>
                        $e->getLine(),

                    'trace' =>
                        $e->getTraceAsString(),

                    'asset_id' =>
                        $request->id ?? null,

                    'company_id' =>
                        $companyId,

                    'delete_images' =>
                        $request->input(
                            'delete_images',
                            []
                        ),

                    'delete_invoice_documents' =>
                        $request->input(
                            'delete_invoice_documents',
                            []
                        ),
                ]
            );


            return response()->json([

                'success' =>
                    false,

                'message' =>
                    'Gagal memperbarui asset.',

                'error' =>
                    $e->getMessage()

            ], 500);
        }
    }


    /**
     * =========================================================================
     * DELETE ASSET
     * =========================================================================
     */
    public function destroy(Request $request)
    {
        $companyId =
            Auth::user()->company_id;


        $asset =
            Asset::where(
                'company_id',
                $companyId
            )
                ->with([
                    'photos',
                    'documents'
                ])
                ->where(
                    'id',
                    $request->id
                )
                ->first();


        if (!$asset) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Asset tidak ditemukan.'
            ], 404);
        }


        DB::beginTransaction();


        try {

            /*
            |--------------------------------------------------------------------------
            | DELETE PHYSICAL PHOTOS
            |--------------------------------------------------------------------------
            */

            foreach (
                $asset->photos
                as $photo
            ) {

                $fullPath =
                    storage_path(
                        'app/public/' . $photo->file_path
                    );

                if (
                    file_exists(
                        $fullPath
                    )
                ) {

                    unlink($fullPath);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | DELETE PHYSICAL DOCUMENTS
            |--------------------------------------------------------------------------
            */

            foreach (
                $asset->documents
                as $document
            ) {

                $fullPath =
                    storage_path(
                        'app/public/' . $document->file_path
                    );

                if (
                    file_exists(
                        $fullPath
                    )
                ) {

                    unlink($fullPath);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | DELETE MAINTENANCE
            |--------------------------------------------------------------------------
            |
            | Semua record maintenance yang berhubungan dengan
            | asset ini ikut dihapus.
            |
            */

            Maintenance::where(
                'company_id',
                $companyId
            )
                ->where(
                    'asset_id',
                    $asset->id
                )
                ->delete();


            /*
            |--------------------------------------------------------------------------
            | DELETE ASSET
            |--------------------------------------------------------------------------
            */

            $asset->delete();


            DB::commit();


            return response()->json([
                'success' => true,
                'message' =>
                    'Asset berhasil dihapus.'
            ]);


        } catch (\Throwable $e) {

            DB::rollBack();


            Log::error(
                'Gagal menghapus asset',
                [

                    'message' =>
                        $e->getMessage(),

                    'asset_id' =>
                        $request->id ?? null,

                    'company_id' =>
                        $companyId,
                ]
            );


            return response()->json([
                'success' => false,
                'message' =>
                    'Gagal menghapus asset.',
                'error' =>
                    $e->getMessage()
            ], 500);
        }
    }


    /**
     * =========================================================================
     * SHOW
     * =========================================================================
     */
    public function show($id)
    {
        $companyId = Auth::user()->company_id;

        $asset = Asset::with([
            'category',
            'subCategory',
            'vendor',
            'responsibleUser',
            'photos',
            'documents',
        ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return view(
            'dashboard.asset.show',
            compact('asset')
        );
    }


    /**
     * =========================================================================
     * QR
     * =========================================================================
     */
    public function qr($id)
    {
        $companyId = Auth::user()->company_id;

        $asset = Asset::where('company_id', $companyId)
            ->where('id', $id)
            ->firstOrFail();

        return view(
            'dashboard.asset.qr',
            compact('asset')
        );
    }


    /**
     * =========================================================================
     * PRINT QR
     * =========================================================================
     */
    public function printQr(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $ids = $request->input('ids', []);

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $ids = array_filter($ids);

        if (empty($ids)) {

            return redirect()
                ->route('assets.index')
                ->with(
                    'error',
                    'Silakan pilih asset yang ingin dicetak.'
                );
        }

        $assets = Asset::where('company_id', $companyId)
            ->whereIn('id', $ids)
            ->with([
                'company'
            ])
            ->orderBy('asset_code')
            ->get();

        if ($assets->isEmpty()) {

            return redirect()
                ->route('assets.index')
                ->with(
                    'error',
                    'Asset tidak ditemukan.'
                );
        }

        return view(
            'dashboard.asset.print-qr',
            compact('assets')
        );
    }


    /**
     * =========================================================================
     * IMPORT
     * =========================================================================
     */
    public function import()
    {
        return view('dashboard.asset.import');
    }


    /**
     * Download Import Template
     */
    public function downloadImportTemplate()
    {
        return Excel::download(
            new AssetImportTemplateExport,
            'asset_import_template.xlsx'
        );
    }


    /**
     * =========================================================================
     * PREVIEW IMPORT
     * =========================================================================
     */
    public function previewImport(Request $request)
    {
        $request->validate([
            'excel_file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240',
            ],
        ]);

        try {

            /*
            |--------------------------------------------------------------------------
            | SIMPAN FILE SEMENTARA
            |--------------------------------------------------------------------------
            */

            $file = $request->file('excel_file');

            $fileName =
                uniqid('asset_import_') .
                '.' .
                $file->getClientOriginalExtension();

            $filePath = $file->storeAs(
                'temp/asset-import',
                $fileName
            );


            /*
            |--------------------------------------------------------------------------
            | FULL PATH
            |--------------------------------------------------------------------------
            */

            $fullPath =
                Storage::path($filePath);


            /*
            |--------------------------------------------------------------------------
            | PREVIEW EXCEL
            |--------------------------------------------------------------------------
            */

            $previewService =
                new ExcelPreviewService();

            $preview =
                $previewService->preview(
                    $fullPath,
                    100
                );

            $totalRows =
                (int) $preview['totalRows'];


            if ($totalRows > 10000) {

                Storage::delete($filePath);

                return back()
                    ->withInput()
                    ->withErrors([
                        'excel_file' =>
                            'Import ditolak. Maksimal 10.000 data per file. ' .
                            'File Anda memiliki ' .
                            number_format($totalRows) .
                            ' data.',
                    ]);
            }


            /*
            |--------------------------------------------------------------------------
            | SIMPAN FILE + TOTAL ROWS KE SESSION
            |--------------------------------------------------------------------------
            */

            session([
                'asset_import_file' =>
                    $filePath,

                'asset_import_total_rows' =>
                    $preview['totalRows'],
            ]);


            /*
            |--------------------------------------------------------------------------
            | RETURN PREVIEW
            |--------------------------------------------------------------------------
            */

            return view(
                'dashboard.asset.import-preview',
                [
                    'data' =>
                        $preview['data'],

                    'totalRows' =>
                        $preview['totalRows'],

                    'previewRows' =>
                        $preview['previewRows'],
                ]
            );

        } catch (\Throwable $e) {

            Log::error(
                'Asset import preview gagal',
                [
                    'error' =>
                        $e->getMessage(),

                    'trace' =>
                        $e->getTraceAsString(),
                ]
            );

            if (!empty($filePath ?? null)) {
                Storage::delete($filePath);
            }

            return back()->with(
                'error',
                'File Excel gagal dibaca: ' .
                $e->getMessage()
            );
        }
    }


    /**
     * =========================================================================
     * IMPORT STORE
     * =========================================================================
     *
     * Tidak membuat Maintenance di sini.
     *
     * Maintenance untuk Asset Import akan dibuat di:
     * ProcessAssetImport::handle()
     *
     */
    public function importStore(Request $request)
    {
        $filePath =
            session('asset_import_file');

        $totalRows =
            (int) session(
                'asset_import_total_rows',
                0
            );

        if (!$filePath) {

            return redirect()
                ->route('assets.import')
                ->with(
                    'error',
                    'File import tidak ditemukan atau session telah berakhir.'
                );
        }

        try {

            /*
            |--------------------------------------------------------------------------
            | CHECK FILE
            |--------------------------------------------------------------------------
            */

            if (!Storage::exists($filePath)) {

                session()->forget([
                    'asset_import_file',
                    'asset_import_total_rows',
                ]);

                return redirect()
                    ->route('assets.import')
                    ->with(
                        'error',
                        'File import sudah tidak tersedia. Silakan upload kembali.'
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | CURRENT USER
            |--------------------------------------------------------------------------
            */

            $user = Auth::user();


            /*
            |--------------------------------------------------------------------------
            | CREATE IMPORT HISTORY
            |--------------------------------------------------------------------------
            */

            $history = ImportHistory::create([

                'company_id' =>
                    $user->company_id,

                'user_id' =>
                    $user->id,

                'module' =>
                    'asset',

                'file_name' =>
                    basename($filePath),

                'total_rows' =>
                    $totalRows,

                'success_rows' =>
                    0,

                'failed_rows' =>
                    0,

                'status' =>
                    'processing',

                'started_at' =>
                    null,

                'finished_at' =>
                    null,
            ]);


            /*
            |--------------------------------------------------------------------------
            | DISPATCH JOB
            |--------------------------------------------------------------------------
            */

            ProcessAssetImport::dispatch(
                $history->id,
                $filePath
            );


            /*
            |--------------------------------------------------------------------------
            | CLEAR SESSION
            |--------------------------------------------------------------------------
            */

            session()->forget([
                'asset_import_file',
                'asset_import_total_rows',
            ]);


            /*
            |--------------------------------------------------------------------------
            | REDIRECT
            |--------------------------------------------------------------------------
            */

            return redirect()
                ->route('import.history')
                ->with(
                    'success',
                    'Import asset berhasil dimasukkan ke antrian. Proses akan berjalan di background.'
                );

        } catch (\Throwable $e) {

            Log::error(
                'Asset import queue gagal',
                [
                    'error' =>
                        $e->getMessage(),

                    'file' =>
                        $filePath,
                ]
            );

            return redirect()
                ->route('assets.import')
                ->with(
                    'error',
                    'Import gagal dimasukkan ke antrian: ' .
                    $e->getMessage()
                );
        }
    }


    /**
     * =========================================================================
     * IMPORT HISTORY
     * =========================================================================
     */
    public function importHistory()
    {
        return view(
            'dashboard.asset.import-history'
        );
    }


    /**
     * =========================================================================
     * IMPORT HISTORY PROGRESS
     * =========================================================================
     */
    public function importHistoryProgress()
    {
        $histories =
            ImportHistory::where(
                'company_id',
                Auth::user()->company_id
            )
                ->where(
                    'module',
                    'asset'
                )
                ->latest()
                ->get();

        $data =
            $histories->map(
                function ($history) {

                    $total =
                        (int) $history->total_rows;

                    $success =
                        (int) $history->success_rows;

                    $failed =
                        (int) $history->failed_rows;

                    $processed =
                        $success +
                        $failed;

                    $progress =
                        $total > 0
                            ? min(
                                round(
                                    ($processed / $total) *
                                    100
                                ),
                                100
                            )
                            : 0;


                    /*
                    |--------------------------------------------------------------------------
                    | DURATION
                    |--------------------------------------------------------------------------
                    */

                    $duration = null;

                    if ($history->started_at) {

                        $endTime =
                            $history->finished_at ??
                            now();

                        $seconds =
                            $history->started_at
                                ->diffInSeconds(
                                    $endTime
                                );

                        $hours =
                            intdiv(
                                $seconds,
                                3600
                            );

                        $minutes =
                            intdiv(
                                $seconds % 3600,
                                60
                            );

                        $remainingSeconds =
                            $seconds % 60;


                        if ($hours > 0) {

                            $duration =
                                $hours . ' jam';

                            if ($minutes > 0) {

                                $duration .=
                                    ' ' .
                                    $minutes .
                                    ' menit';
                            }

                            if (
                                $remainingSeconds >
                                0
                            ) {

                                $duration .=
                                    ' ' .
                                    $remainingSeconds .
                                    ' detik';
                            }

                        } elseif ($minutes > 0) {

                            $duration =
                                $minutes .
                                ' menit';

                            if (
                                $remainingSeconds >
                                0
                            ) {

                                $duration .=
                                    ' ' .
                                    $remainingSeconds .
                                    ' detik';
                            }

                        } else {

                            $duration =
                                $remainingSeconds .
                                ' detik';
                        }


                        if (
                            $history->status ===
                            'processing'
                        ) {

                            $duration .=
                                ' (berjalan)';
                        }
                    }


                    return [

                        'id' =>
                            $history->id,

                        'file_name' =>
                            $history->file_name,

                        'total_rows' =>
                            $total,

                        'success_rows' =>
                            $success,

                        'failed_rows' =>
                            $failed,

                        'processed_rows' =>
                            $processed,

                        'progress' =>
                            $progress,

                        'status' =>
                            $history->status,

                        'started_at' =>
                            $history->started_at
                                ? $history
                                    ->started_at
                                    ->format(
                                        'd M Y H:i:s'
                                    )
                                : null,

                        'finished_at' =>
                            $history->finished_at
                                ? $history
                                    ->finished_at
                                    ->format(
                                        'd M Y H:i:s'
                                    )
                                : null,

                        'duration' =>
                            $duration,
                    ];
                }
            );

        return response()->json($data);
    }


    /**
     * =========================================================================
     * CALCULATE NEXT MAINTENANCE DATE
     * =========================================================================
     */
    private function calculateNextMaintenanceDate(
        Request $request
    ): ?Carbon {

        if (
            !$request->boolean(
                'maintenance_required'
            )
        ) {
            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | START DATE
        |--------------------------------------------------------------------------
        */

        $startDate =
            $request->maintenance_start_date;


        /*
        |--------------------------------------------------------------------------
        | INTERVAL
        |--------------------------------------------------------------------------
        */

        $interval =
            $request->maintenance_interval;


        /*
        |--------------------------------------------------------------------------
        | UNIT
        |--------------------------------------------------------------------------
        */

        $unit =
            $request->maintenance_interval_unit;


        /*
        |--------------------------------------------------------------------------
        | Jika belum lengkap, jangan membuat tanggal.
        |--------------------------------------------------------------------------
        */

        if (
            !$startDate ||
            !$interval ||
            !$unit
        ) {
            return null;
        }


        $date =
            Carbon::parse(
                $startDate
            );

        $interval =
            (int) $interval;


        switch ($unit) {

            case 'day':

                return $date
                    ->copy()
                    ->addDays(
                        $interval
                    );

            case 'week':

                return $date
                    ->copy()
                    ->addWeeks(
                        $interval
                    );

            case 'month':

                return $date
                    ->copy()
                    ->addMonths(
                        $interval
                    );

            case 'year':

                return $date
                    ->copy()
                    ->addYears(
                        $interval
                    );

            default:

                return null;
        }
    }


    /**
     * =========================================================================
     * SYNC MAINTENANCE SCHEDULE
     * =========================================================================
     *
     * Aturan:
     *
     * 1. Maintenance OFF
     *    -> hapus schedule "scheduled".
     *
     * 2. Maintenance ON + tanggal tersedia
     *    -> update schedule "scheduled" jika sudah ada.
     *    -> jika belum ada, buat schedule baru.
     *
     * 3. Maintenance "in_progress"
     *    -> jangan disentuh.
     *
     * 4. Maintenance "completed"
     *    -> jangan disentuh.
     *
     */
    private function syncMaintenanceSchedule(
        Asset $asset,
        int $companyId,
        bool $maintenanceRequired,
        ?Carbon $nextMaintenanceDate
    ): void {

        /*
        |--------------------------------------------------------------------------
        | MAINTENANCE OFF
        |--------------------------------------------------------------------------
        |
        | Jika maintenance dimatikan, hanya schedule yang masih
        | berstatus scheduled yang dihapus.
        |
        | completed dan in_progress tetap aman.
        |
        */

        if (
            !$maintenanceRequired ||
            !$nextMaintenanceDate
        ) {

            Maintenance::where(
                'company_id',
                $companyId
            )
                ->where(
                    'asset_id',
                    $asset->id
                )
                ->where(
                    'status',
                    'scheduled'
                )
                ->delete();

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | CARI SCHEDULE AKTIF
        |--------------------------------------------------------------------------
        |
        | HANYA status scheduled yang boleh disinkronkan
        |
        | Jangan mengambil in_progress karena maintenance yang
        | sedang dikerjakan tidak boleh tiba-tiba dikembalikan
        | menjadi scheduled.
        |
        */

        $activeMaintenance =
            Maintenance::where(
                'company_id',
                $companyId
            )
                ->where(
                    'asset_id',
                    $asset->id
                )
                ->where(
                    'status',
                    'scheduled'
                )
                ->orderBy(
                    'maintenance_date'
                )
                ->first();


        /*
        |--------------------------------------------------------------------------
        | UPDATE EXISTING SCHEDULE
        |--------------------------------------------------------------------------
        */

        if ($activeMaintenance) {

            $activeMaintenance->update([

                'maintenance_date' =>
                    $nextMaintenanceDate,

                'maintenance_type' =>
                    $asset->maintenance_type,

                'vendor_id' =>
                    $asset->vendor_id,
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | CREATE NEW SCHEDULE
        |--------------------------------------------------------------------------
        |
        | Jika tidak ada scheduled:
        |
        | - completed ada -> tetap dipertahankan
        | - in_progress ada -> tetap dipertahankan
        | - scheduled tidak ada -> buat schedule baru
        |
        */

        Maintenance::create([

            'company_id' =>
                $companyId,

            'asset_id' =>
                $asset->id,

            'maintenance_code' =>
                $this->generateMaintenanceCode(
                    $companyId
                ),

            'maintenance_date' =>
                $nextMaintenanceDate,

            'maintenance_type' =>
                $asset->maintenance_type,

            'vendor_id' =>
                $asset->vendor_id,

            'status' =>
                'scheduled',

            'description' =>
                null,

            'notes' =>
                null,

            'created_by' =>
                Auth::id(),
        ]);
    }


    /**
     * =========================================================================
     * GENERATE MAINTENANCE CODE
     * =========================================================================
     */
    private function generateMaintenanceCode(
        int $companyId
    ): string {

        do {

            $code =
                'MNT-' .
                strtoupper(
                    Str::random(10)
                );

        } while (
            Maintenance::where(
                'company_id',
                $companyId
            )
                ->where(
                    'maintenance_code',
                    $code
                )
                ->exists()
        );

        return $code;
    }
}