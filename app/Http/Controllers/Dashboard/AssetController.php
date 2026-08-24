<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Vendor;
use App\Models\User;
use App\Helpers\CodeHelper;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

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

        $query = Asset::where('company_id', $companyId)
            ->with([
                'category',
                'subCategory',
                'vendor',
                'responsibleUser'
            ])
            ->latest();

        return DataTables::of($query)

            ->addIndexColumn()

            /*
            |--------------------------------------------------------------------------
            | PHOTO
            |--------------------------------------------------------------------------
            */

            ->addColumn('photo', function ($asset) {

                // Sementara placeholder
                // Nanti akan kita ganti dengan foto pertama aset

                return '
                    <div class="asset-photo-placeholder">
                        <i class="fa-solid fa-box"></i>
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
            | RESPONSIBLE USER
            |--------------------------------------------------------------------------
            */

            ->addColumn('responsible_name', function ($asset) {

                return $asset->responsibleUser
                    ? $asset->responsibleUser->name
                    : '-';
            })

            /*
            |--------------------------------------------------------------------------
            | PURCHASE PRICE
            |--------------------------------------------------------------------------
            */

            ->editColumn('purchase_date', function ($asset) {

                if (!$asset->purchase_date) {
                    return '-';
                }

                return Carbon::parse($asset->purchase_date)
                    ->translatedFormat('d F Y');
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
                            href="' . route('assets.edit', $asset->id) . '"
                            class="btn btn-sm btn-warning"
                            title="Edit">
                            <i class="fa fa-edit"></i>
                        </a>
                        <button
                            type="button"
                            class="btn btn-sm btn-danger btn-delete"
                            data-id="' . $asset->id . '"
                            title="Delete">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                ';
            })

            ->rawColumns([
                'photo',
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
                'asset_name' => 'required|string|max:255',

                /*
                |--------------------------------------------------------------------------
                | CATEGORY
                |--------------------------------------------------------------------------
                | Bisa:
                | 1. ID category existing
                | 2. new:Nama Category
                |--------------------------------------------------------------------------
                */
                'category_id' => [
                    'required',
                ],

                /*
                |--------------------------------------------------------------------------
                | SUB CATEGORY
                |--------------------------------------------------------------------------
                */
                'sub_category_id' => [
                    'nullable',
                ],

                'vendor_id' => [
                    'required',
                    'exists:vendors,id'
                ],

                'responsible_user_id' => [
                    'nullable',
                    'exists:users,id'
                ],

                'brand' => 'nullable|string|max:100',
                'model' => 'nullable|string|max:150',
                'serial_number' => 'nullable|string|max:150',

                'description' => 'nullable|string',

                'purchase_date' => 'nullable|date',

                'purchase_price' => [
                    'nullable',
                    'numeric',
                    'min:0'
                ],

                'invoice_number' => 'nullable|string|max:100',

                'depreciation_method' => 'nullable|string|max:50',

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

                'depreciation_start_date' => 'nullable|date',

                'warranty_start' => 'nullable|date',

                'warranty_end' => [
                    'nullable',
                    'date',
                    'after_or_equal:warranty_start'
                ],

                'warranty_note' => 'nullable|string',

                'location' => 'nullable|string|max:255',

                'status' => 'required|integer|in:0,1',

                /*
                |--------------------------------------------------------------------------
                | IMAGES
                |--------------------------------------------------------------------------
                */
                'asset_photos' => [
                    'nullable',
                    'array',
                    'max:3'
                ],

                'asset_photos.*' => [
                    'image',
                    'mimes:jpg,jpeg,png',
                    'max:5120'
                ],

                /*
                |--------------------------------------------------------------------------
                | DOCUMENTS
                |--------------------------------------------------------------------------
                */
                'invoice_documents' => [
                    'nullable',
                    'array',
                    'max:5'
                ],

                'invoice_documents.*' => [
                    'file',
                    'mimes:pdf,jpg,jpeg,png',
                    'max:10240'
                ],
            ],
            [
                'asset_name.required' =>
                    'Nama asset wajib diisi',

                'category_id.required' =>
                    'Kategori wajib dipilih atau diisi',

                'vendor_id.required' =>
                    'Vendor wajib dipilih',

                'vendor_id.exists' =>
                    'Vendor tidak valid',

                'responsible_user_id.exists' =>
                    'Responsible user tidak valid',

                'purchase_price.numeric' =>
                    'Harga pembelian harus berupa angka',

                'purchase_price.min' =>
                    'Harga pembelian tidak boleh kurang dari 0',

                'useful_life.integer' =>
                    'Umur manfaat harus berupa angka',

                'useful_life.min' =>
                    'Umur manfaat minimal 1 tahun',

                'warranty_end.after_or_equal' =>
                    'Tanggal akhir warranty tidak boleh sebelum tanggal mulai',

                'status.required' =>
                    'Status wajib dipilih',

                'asset_photos.max' =>
                    'Foto asset maksimal 3 file',

                'asset_photos.*.image' =>
                    'File foto harus berupa gambar',

                'asset_photos.*.mimes' =>
                    'Foto hanya boleh JPG, JPEG atau PNG',

                'asset_photos.*.max' =>
                    'Ukuran setiap foto maksimal 5 MB',

                'invoice_documents.max' =>
                    'Dokumen maksimal 5 file',

                'invoice_documents.*.mimes' =>
                    'Dokumen hanya boleh PDF, JPG, JPEG atau PNG',

                'invoice_documents.*.max' =>
                    'Ukuran setiap dokumen maksimal 10 MB',
            ]
        );


        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | DATABASE TRANSACTION
        |--------------------------------------------------------------------------
        */

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | CATEGORY
            |--------------------------------------------------------------------------
            */

            $categoryValue = $request->category_id;

            /*
            |--------------------------------------------------------------------------
            | EXISTING CATEGORY
            |--------------------------------------------------------------------------
            */

            if (
                is_numeric($categoryValue)
            ) {

                $category = Category::where('company_id', $companyId)
                    ->where('id', $categoryValue)
                    ->first();

                if (!$category) {

                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Kategori tidak valid.'
                    ], 422);
                }

            }

            /*
            |--------------------------------------------------------------------------
            | NEW CATEGORY
            |--------------------------------------------------------------------------
            */

            else {

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
                        'message' => 'Nama kategori wajib diisi.'
                    ], 422);
                }


                /*
                |--------------------------------------------------------------------------
                | CHECK DUPLICATE CATEGORY
                |--------------------------------------------------------------------------
                */

                $category = Category::where(
                    'company_id',
                    $companyId
                )
                    ->whereRaw(
                        'LOWER(category_name) = ?',
                        [strtolower($categoryName)]
                    )
                    ->first();


                /*
                |--------------------------------------------------------------------------
                | CREATE CATEGORY
                |--------------------------------------------------------------------------
                */

                if (!$category) {

                    $categoryCode = CodeHelper::generateNumber(
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


                /*
                |--------------------------------------------------------------------------
                | EXISTING SUB CATEGORY
                |--------------------------------------------------------------------------
                */

                if (
                    is_numeric($subCategoryValue)
                ) {

                    $subCategory = SubCategory::where(
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
                }


                /*
                |--------------------------------------------------------------------------
                | NEW SUB CATEGORY
                |--------------------------------------------------------------------------
                */

                else {

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


                    $subCategoryName = trim(
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


                    /*
                    |--------------------------------------------------------------------------
                    | CHECK DUPLICATE SUB CATEGORY
                    |--------------------------------------------------------------------------
                    */

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


                    /*
                    |--------------------------------------------------------------------------
                    | CREATE SUB CATEGORY
                    |--------------------------------------------------------------------------
                    */

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
            | VENDOR - PASTIKAN MILIK COMPANY
            |--------------------------------------------------------------------------
            */

            if (
                !Vendor::where(
                    'company_id',
                    $companyId
                )
                    ->where(
                        'id',
                        $request->vendor_id
                    )
                    ->exists()
            ) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Vendor tidak valid.'
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | RESPONSIBLE USER - PASTIKAN MILIK COMPANY
            |--------------------------------------------------------------------------
            */

            if (
                $request->filled(
                    'responsible_user_id'
                )
            ) {

                if (
                    !User::where(
                        'company_id',
                        $companyId
                    )
                        ->where(
                            'id',
                            $request->responsible_user_id
                        )
                        ->exists()
                ) {

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
            | GENERATE ASSET CODE
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
            | GENERATE QR TOKEN
            |--------------------------------------------------------------------------
            */

            $qrToken =
                Str::uuid()->toString();


            /*
            |--------------------------------------------------------------------------
            | UPLOAD IMAGES
            |--------------------------------------------------------------------------
            */

            $images = [];

            if ($request->hasFile('asset_photos')) {

                foreach (
                    $request->file('asset_photos')
                    as $file
                ) {

                    $filename =
                        $assetCode . '-' .
                        Str::uuid() . '.' .
                        $file->getClientOriginalExtension();


                    $path =
                        $file->storeAs(
                            'assets/images',
                            $filename,
                            'public'
                        );


                    $images[] = $path;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | UPLOAD INVOICE DOCUMENTS
            |--------------------------------------------------------------------------
            */

            $invoiceDocuments = [];

            if (
                $request->hasFile(
                    'invoice_documents'
                )
            ) {

                foreach (
                    $request->file(
                        'invoice_documents'
                    )
                    as $file
                ) {

                    $filename =
                        $assetCode . '-' .
                        Str::uuid() . '.' .
                        $file->getClientOriginalExtension();


                    $path =
                        $file->storeAs(
                            'assets/documents',
                            $filename,
                            'public'
                        );


                    $invoiceDocuments[] =
                        $path;
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
                    $request->invoice_number,

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

                'images' =>
                    !empty($images)
                        ? $images
                        : null,

                'invoice_documents' =>
                    !empty($invoiceDocuments)
                        ? $invoiceDocuments
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
            | COMMIT
            |--------------------------------------------------------------------------
            */

            DB::commit();


            return response()->json([
                'success' => true,
                'message' =>
                    'Asset berhasil ditambahkan.',
                'data' => $asset
            ]);


        } catch (\Exception $e) {

            DB::rollBack();


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

        $asset = Asset::where('company_id', $companyId)
            ->with([
                'category',
                'subCategory',
                'vendor',
                'responsibleUser'
            ])
            ->where('id', $id)
            ->first();

        if (!$asset) {
            abort(404, 'Asset tidak ditemukan.');
        }

        $categories = Category::where('company_id', $companyId)
            ->where('status', 1)
            ->get();

        $subCategories = SubCategory::where('company_id', $companyId)
            ->where('status', 1)
            ->get();

        $vendors = Vendor::where('company_id', $companyId)
            ->where('status', 1)
            ->get();

        $users = User::where('company_id', $companyId)
            ->where('status', 1)
            ->get();

        return view('dashboard.asset.edit', compact(
            'asset',
            'categories',
            'subCategories',
            'vendors',
            'users'
        ));
    }


    /**
     * Update Asset
     */
    public function update(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $validator = Validator::make(
            $request->all(),
            [
                'id' => 'required',

                'asset_name' => 'required|string|max:255',

                'category_id' => 'required|exists:categories,id',

                'sub_category_id' =>
                    'nullable|exists:sub_categories,id',

                'vendor_id' =>
                    'nullable|exists:vendors,id',

                'responsible_user_id' =>
                    'nullable|exists:users,id',

                'brand' => 'nullable|string|max:100',

                'model' => 'nullable|string|max:150',

                'serial_number' =>
                    'nullable|string|max:150',

                'description' => 'nullable|string',

                'purchase_date' => 'nullable|date',

                'purchase_price' =>
                    'nullable|numeric|min:0',

                'purchase_invoice' =>
                    'nullable|string|max:100',

                'depreciation_method' =>
                    'nullable|string|max:50',

                'useful_life' =>
                    'nullable|integer|min:1',

                'residual_value' =>
                    'nullable|numeric|min:0',

                'depreciation_start_date' =>
                    'nullable|date',

                'warranty_start' =>
                    'nullable|date',

                'warranty_end' =>
                    'nullable|date|after_or_equal:warranty_start',

                'warranty_note' =>
                    'nullable|string',

                'location' =>
                    'nullable|string|max:255',

                'status' =>
                    'required|integer|in:0,1',
            ]
        );


        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }


        $asset = Asset::where('company_id', $companyId)
            ->where('id', $request->id)
            ->first();


        if (!$asset) {

            return response()->json([
                'success' => false,
                'message' => 'Asset tidak ditemukan.'
            ], 404);
        }


        try {

            $asset->update([

                'asset_name' =>
                    strtoupper(trim($request->asset_name)),

                'category_id' =>
                    $request->category_id,

                'sub_category_id' =>
                    $request->sub_category_id,

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
            ]);


            return response()->json([
                'success' => true,
                'message' => 'Asset berhasil diperbarui.',
                'data' => $asset
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui asset.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Delete Asset
     */
    public function destroy(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $asset = Asset::where('company_id', $companyId)
            ->where('id', $request->id)
            ->first();

        if (!$asset) {

            return response()->json([
                'success' => false,
                'message' => 'Asset tidak ditemukan.'
            ], 404);
        }


        try {

            $asset->delete();

            return response()->json([
                'success' => true,
                'message' => 'Asset berhasil dihapus.'
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus asset.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}