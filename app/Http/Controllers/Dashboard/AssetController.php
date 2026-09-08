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
        |
        | Search Asset Code / Asset Name
        |
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

            /*
            |--------------------------------------------------------------------------
            | NO
            |--------------------------------------------------------------------------
            */

            ->addIndexColumn()


            /*
            |--------------------------------------------------------------------------
            | CHECKBOX
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | CATEGORY
            |--------------------------------------------------------------------------
            */

            ->addColumn('category_name', function ($asset) {

                return $asset->category
                    ? $asset->category->category_name
                    : '-';
            })


            /*
            |--------------------------------------------------------------------------
            | SUB CATEGORY
            |--------------------------------------------------------------------------
            */

            ->addColumn('sub_category_name', function ($asset) {

                return $asset->subCategory
                    ? $asset->subCategory->sub_category_name
                    : '-';
            })


            /*
            |--------------------------------------------------------------------------
            | VENDOR
            |--------------------------------------------------------------------------
            */

            ->addColumn('vendor_name', function ($asset) {

                return $asset->vendor
                    ? $asset->vendor->vendor_name
                    : '-';
            })


            /*
            |--------------------------------------------------------------------------
            | RESPONSIBLE
            |--------------------------------------------------------------------------
            */

            ->addColumn('responsible_name', function ($asset) {

                return $asset->responsibleUser
                    ? $asset->responsibleUser->name
                    : '-';
            })


            /*
            |--------------------------------------------------------------------------
            | PURCHASE DATE
            |--------------------------------------------------------------------------
            */

            ->editColumn('purchase_date', function ($asset) {

                if (!$asset->purchase_date) {
                    return '-';
                }

                return Carbon::parse(
                    $asset->purchase_date
                )->translatedFormat('d F Y');
            })


            /*
            |--------------------------------------------------------------------------
            | PURCHASE PRICE
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | ACTION
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | RAW HTML
            |--------------------------------------------------------------------------
            */

            ->rawColumns([
                'checkbox',
                'status',
                'action'
            ])

            ->make(true);
    }


    /**
     * Store Asset
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
                'maintenance_required' => [
                    'required',
                    'boolean'
                ],

                'maintenance_type' => [
                    'nullable',
                    'string',
                    'max:50'
                ],

                'maintenance_trigger' => [
                    'nullable',
                    'string',
                    'max:50'
                ],

                'maintenance_interval' => [
                    'nullable',
                    'integer',
                    'min:1'
                ],

                'maintenance_interval_unit' => [
                    'nullable',
                    'in:day,week,month,year'
                ],

                'maintenance_start_date' => [
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
                'asset_condition.required' =>
                    'Kondisi asset wajib diisi.',

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
                                'SUB-',
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
            | CREATE ASSET
            |--------------------------------------------------------------------------
            */
            $nextMaintenanceDate = null;
            if ($request->boolean('maintenance_required')) {
                if (
                    $request->maintenance_start_date &&
                    $request->maintenance_interval
                ) {
                    $startDate = Carbon::parse(
                        $request->maintenance_start_date
                    );
                    $interval = (int) $request->maintenance_interval;
                    switch ($request->maintenance_interval_unit) {
                        case 'day':
                            $nextMaintenanceDate =
                                $startDate->copy()->addDays($interval);
                            break;
                        case 'week':
                            $nextMaintenanceDate =
                                $startDate->copy()->addWeeks($interval);
                            break;
                        case 'month':
                            $nextMaintenanceDate =
                                $startDate->copy()->addMonths($interval);
                            break;
                        case 'year':
                            $nextMaintenanceDate =
                                $startDate->copy()->addYears($interval);
                            break;
                    }
                }
            }
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

                'asset_condition'=>
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
                    $request->boolean('maintenance_required'),

                'maintenance_type' =>
                    $request->maintenance_required
                        ? $request->maintenance_type
                        : null,

                'maintenance_trigger' =>
                    $request->maintenance_required
                        ? $request->maintenance_trigger
                        : null,

                'maintenance_interval' =>
                    $request->maintenance_required
                        ? $request->maintenance_interval
                        : null,

                'maintenance_interval_unit' =>
                    $request->maintenance_required
                        ? $request->maintenance_interval_unit
                        : null,

                'maintenance_start_date' =>
                    $request->maintenance_required
                        ? $request->maintenance_start_date
                        : null,

                'last_maintenance_date' =>
                    $request->maintenance_required
                        ? $request->last_maintenance_date
                        : null,

                'next_maintenance_date' =>
                    $request->maintenance_required
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

                    /*
                    |--------------------------------------------------------------------------
                    | STORAGE PATH
                    |--------------------------------------------------------------------------
                    */

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

                    /*
                    |--------------------------------------------------------------------------
                    | DATABASE PATH
                    |--------------------------------------------------------------------------
                    */

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

                    /*
                    |--------------------------------------------------------------------------
                    | STORAGE PATH
                    |--------------------------------------------------------------------------
                    */

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

                    /*
                    |--------------------------------------------------------------------------
                    | DATABASE PATH
                    |--------------------------------------------------------------------------
                    */

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
                    //public_path($path);
                    storage_path(
                        'app/public/' . $path
                    );

                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }

            foreach ($uploadedInvoicePaths as $path) {

                $fullPath =
                    //public_path($path);
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
     * Edit Asset
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
     * Update Asset
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
                'maintenance_required' => [
                    'required',
                    'boolean',
                ],

                'maintenance_type' => [
                    'nullable',
                    'string',
                    'in:preventive,corrective',
                ],

                'maintenance_trigger' => [
                    'nullable',
                    'string',
                    'in:calendar',
                ],

                'maintenance_interval' => [
                    'nullable',
                    'integer',
                    'min:1',
                ],

                'maintenance_interval_unit' => [
                    'nullable',
                    'string',
                    'in:day,week,month,year',
                ],

                'maintenance_start_date' => [
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

                /*
                |--------------------------------------------------------------------------
                | DELETE PHOTOS
                |--------------------------------------------------------------------------
                */
                'delete_images' => [
                    'nullable',
                    'array'
                ],
                'delete_images.*' => [
                    'integer'
                ],
                /*
                |--------------------------------------------------------------------------
                | NEW PHOTOS
                |--------------------------------------------------------------------------
                */
                'asset_photos' => [
                    'nullable',
                    'array'
                ],
                'asset_photos.*' => [
                    'file',
                    'max:5120',
                    'mimes:jpg,jpeg,png'
                ],
                /*
                |--------------------------------------------------------------------------
                | DELETE DOCUMENTS
                |--------------------------------------------------------------------------
                */
                'delete_invoice_documents' => [
                    'nullable',
                    'array'
                ],
                'delete_invoice_documents.*' => [
                    'integer'
                ],
                /*
                |--------------------------------------------------------------------------
                | NEW DOCUMENTS
                |--------------------------------------------------------------------------
                */
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
            |
            | IMPORTANT:
            | Sekarang delete berdasarkan ID AssetPhoto.
            |
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
                        /*public_path(
                            $photo->file_path
                        );*/
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

                    /*
                    |--------------------------------------------------------------------------
                    | STORAGE PATH
                    |--------------------------------------------------------------------------
                    */

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

                    /*
                    |--------------------------------------------------------------------------
                    | DATABASE PATH
                    |--------------------------------------------------------------------------
                    */

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
                        /*public_path(
                            $document->file_path
                        );*/
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

                    /*
                    |--------------------------------------------------------------------------
                    | STORAGE PATH
                    |--------------------------------------------------------------------------
                    */

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

                    /*
                    |--------------------------------------------------------------------------
                    | DATABASE PATH
                    |--------------------------------------------------------------------------
                    */

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
                    $request->maintenance_required,

                'maintenance_type' =>
                    $request->maintenance_type,

                'maintenance_trigger' =>
                    $request->maintenance_trigger,

                'maintenance_interval' =>
                    $request->maintenance_interval,

                'maintenance_interval_unit' =>
                    $request->maintenance_interval_unit,

                'maintenance_start_date' =>
                    $request->maintenance_start_date,

                'last_maintenance_date' =>
                    $request->last_maintenance_date,

                'next_maintenance_date' =>
                    $request->next_maintenance_date,
            ]);


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
                    public_path($path);
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
                    public_path($path);

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
     * Delete Asset
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
                    /*public_path(
                        $photo->file_path
                    );*/
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
                    /*public_path(
                        $document->file_path
                    );*/
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
    public function qr($id)
    {
        $companyId = Auth::user()->company_id;

        $asset = Asset::where('company_id', $companyId)
            ->where('id', $id)
            ->firstOrFail();

        return view('dashboard.asset.qr', compact('asset'));
    }

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
                ->with('error', 'Silakan pilih asset yang ingin dicetak.');
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
                ->with('error', 'Asset tidak ditemukan.');
        }

        return view(
            'dashboard.asset.print-qr',
            compact('assets')
        );
    }

    public function import()
    {
        return view('dashboard.asset.import');
    }
    public function downloadImportTemplate()
    {
        return Excel::download(
            new AssetImportTemplateExport,
            'asset_import_template.xlsx'
        );
    }
    
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
            | Simpan file sementara
            |--------------------------------------------------------------------------
            */

            $file = $request->file('excel_file');

            $fileName = uniqid('asset_import_') . '.' .
                $file->getClientOriginalExtension();

            $filePath = $file->storeAs(
                'temp/asset-import',
                $fileName
            );

            /*
            |--------------------------------------------------------------------------
            | Full path
            |--------------------------------------------------------------------------
            */

            $fullPath = Storage::path($filePath);

            /*
            |--------------------------------------------------------------------------
            | Preview Excel
            |--------------------------------------------------------------------------
            */

            $previewService = new ExcelPreviewService();

            $preview = $previewService->preview(
                $fullPath,
                100
            );

            $totalRows = (int) $preview['totalRows'];
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
            | Simpan file + total rows ke session
            |--------------------------------------------------------------------------
            */

            session([
                'asset_import_file' => $filePath,
                'asset_import_total_rows' => $preview['totalRows'],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Return Preview
            |--------------------------------------------------------------------------
            */

            return view(
                'dashboard.asset.import-preview',
                [
                    'data' => $preview['data'],

                    'totalRows' => $preview['totalRows'],

                    'previewRows' => $preview['previewRows'],
                ]
            );

        } catch (\Throwable $e) {

            Log::error(
                'Asset import preview gagal',
                [
                    'error' => $e->getMessage(),

                    'trace' => $e->getTraceAsString(),
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

    public function importStore(Request $request)
    {
        $filePath = session('asset_import_file');

        $totalRows = (int) session(
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
            | Check File
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
            | Current User
            |--------------------------------------------------------------------------
            */

            $user = Auth::user();

            /*
            |--------------------------------------------------------------------------
            | Create Import History
            |--------------------------------------------------------------------------
            */

            $history = ImportHistory::create([
                'company_id' => $user->company_id,
                'user_id' => $user->id,

                'module' => 'asset',

                'file_name' => basename($filePath),

                'total_rows' => $totalRows,

                'success_rows' => 0,
                'failed_rows' => 0,

                'status' => 'processing',

                'started_at' => null,
                'finished_at' => null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Dispatch Job
            |--------------------------------------------------------------------------
            |
            | Sama seperti User Import.
            | Tidak menggunakan ->onQueue('imports').
            |
            */

            ProcessAssetImport::dispatch(
                $history->id,
                $filePath
            );

            /*
            |--------------------------------------------------------------------------
            | Clear Session
            |--------------------------------------------------------------------------
            */

            session()->forget([
                'asset_import_file',
                'asset_import_total_rows',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Redirect
            |--------------------------------------------------------------------------
            */

            return redirect()
                ->route('import.history')
                ->with(
                    'success',
                    'Import asset berhasil dimasukkan ke antrian. Proses akan berjalan di background.'
                );

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Log Error
            |--------------------------------------------------------------------------
            */

            Log::error(
                'Asset import queue gagal',
                [
                    'error' => $e->getMessage(),
                    'file' => $filePath,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Redirect
            |--------------------------------------------------------------------------
            */

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
        return view('dashboard.asset.import-history');
    }

    public function importHistoryProgress()
    {
        $histories = ImportHistory::where(
            'company_id',
            Auth::user()->company_id
        )
            ->where('module', 'asset')
            ->latest()
            ->get();

        $data = $histories->map(function ($history) {

            $total = (int) $history->total_rows;
            $success = (int) $history->success_rows;
            $failed = (int) $history->failed_rows;

            $processed = $success + $failed;

            $progress = $total > 0
                ? min(
                    round(($processed / $total) * 100),
                    100
                )
                : 0;

            /*
            |--------------------------------------------------------------------------
            | Duration
            |--------------------------------------------------------------------------
            */

            $duration = null;

            if ($history->started_at) {

                $endTime =
                    $history->finished_at ?? now();

                $seconds =
                    $history->started_at
                        ->diffInSeconds($endTime);

                $hours =
                    intdiv($seconds, 3600);

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
                            ' ' . $minutes . ' menit';
                    }

                    if ($remainingSeconds > 0) {
                        $duration .=
                            ' ' .
                            $remainingSeconds .
                            ' detik';
                    }

                } elseif ($minutes > 0) {

                    $duration =
                        $minutes . ' menit';

                    if ($remainingSeconds > 0) {
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
                    $history->status === 'processing'
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
                        ? $history->started_at
                            ->format('d M Y H:i:s')
                        : null,

                'finished_at' =>
                    $history->finished_at
                        ? $history->finished_at
                            ->format('d M Y H:i:s')
                        : null,

                'duration' =>
                    $duration,
            ];
        });

        return response()->json($data);
    }

}