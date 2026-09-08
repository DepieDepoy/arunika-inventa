<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yajra\DataTables\Facades\DataTables;
use App\Helpers\CodeHelper;

class CategoryController extends Controller
{
    /**
     * Halaman Category
     */
    public function index()
    {
        $companyId = Auth::user()->company_id;

        $categories = Category::where('company_id', $companyId)
            ->withCount('assets')
            ->with([
                'subCategories' => function ($query) {
                    $query->withCount('assets');
                }
            ])
            ->latest()
            ->get();

        return view('dashboard.category.index', compact('categories'));
    }

    /**
     * DataTables
     */
    public function data()
    {
        $query = Category::query()
            ->where(
                'company_id',
                Auth::user()->company_id
            )
            ->withCount('assets')
            ->latest('id');

        /** @var User $user */
        $user = Auth::user();
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('category_name', function ($row) {
                return '
                    <div class="category-name-wrapper">
                        <button
                            type="button"
                            class="category-expand btn-expand-category"
                            data-id="' . $row->id . '"
                            title="View Sub Category"
                        >
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                        <div>
                            <div class="category-name">
                                ' . e(ucwords(strtolower($row->category_name))) . '
                            </div>

                            <div class="category-code">
                                ' . e($row->category_code) . '
                            </div>
                        </div>
                    </div>
                ';
            })
            ->editColumn('category_code', function ($row) {
                return '
                    <span class="text-dark">
                        ' . e($row->category_code) . '
                    </span>
                ';
            })
            ->editColumn('description', function ($row) {
                return '
                    <span class="text-dark">
                        ' . e($row->description) . '
                    </span>
                ';
            })
            ->addColumn('total_asset', function ($row) {
                return '
                    <span class="text-dark">
                        ' . e($row->assets_count ?? 0) . '
                    </span>
                ';
            })
            ->editColumn('status', function ($row) {

                if ($row->status == 1) {
                    return '
                        <span class="badge-status badge-active">
                            <i class="fa-solid fa-circle-check me-1"></i>
                            Active
                        </span>
                    ';
                }

                return '
                    <span class="badge-status badge-inactive">
                        <i class="fa-solid fa-circle-xmark me-1"></i>
                        Inactive
                    </span>
                ';
            })

            ->addColumn('action', function ($row) use ($user) {
                $action = '
                    <div class="d-flex justify-content-center align-items-center gap-1">
                ';

                if ($user->hasPermission('category.edit')) {
                    $action .= '
                        <a href="javascript:void(0)"
                        class="btn-action btn-edit"
                        data-id="' . $row->id . '"
                        title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    ';
                }

                if ($user->hasPermission('category.delete')) {
                    $action .= '
                        <a href="javascript:void(0)"
                        class="btn-action btn-delete"
                        data-id="' . $row->id . '"
                        title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    ';
                }

                $action .= '
                    </div>
                ';

                return $action;
            })

            ->rawColumns([
                'category_name',
                'category_code',
                'description',
                'total_asset',
                'status',
                'action'
            ])

            ->make(true);
    }

    /**
     * Store Category
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'category_name' => 'required',
                'status' => 'required',
            ],
            [
                'category_name.required' => 'Nama kategori wajib diisi',
                'status.required' => 'Status wajib dipilih',
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
        | Cek Duplicate Name
        |--------------------------------------------------------------------------
        */
        $categoryName = strtoupper(preg_replace('/\s+/', ' ',trim($request->category_name)));
        $exists = Category::where(
            'company_id',
            Auth::user()->company_id
        )
        ->where('category_name',$categoryName)
        ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'category_code' => [
                        'Nama kategori sudah digunakan'
                    ]
                ]
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Create
        |--------------------------------------------------------------------------
        */
        $companyId = Auth::user()->company_id;

        $code = CodeHelper::generateNumber(
            //$request->category_name,
            'CAT-',
            Category::class,
            'category_code',
            $companyId
        );
        if (
            Category::where('company_id', $companyId)
                ->where('category_code', $code)
                ->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Category code ' . $code . ' sudah digunakan.'
            ], 422);
        }
        Category::create([
            'company_id' => Auth::user()->company_id,
            'category_name' => strtoupper(trim($request->category_name)),
            'category_code' => $code,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil ditambahkan'
        ]);
    }

    /**
     * Edit Category
     */
    public function edit($id)
    {
        $category = Category::where(
            'company_id',
            Auth::user()->company_id
        )
        ->findOrFail($id);

        return response()->json($category);
    }

    /**
     * Update Category
     */
    public function update(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'id' => 'required',
                'category_name' => 'required',
                'status' => 'required',
            ],
            [
                'category_name.required' => 'Nama kategori wajib diisi',
                'status.required' => 'Status wajib dipilih',
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
        | Get Category
        |--------------------------------------------------------------------------
        */

        $category = Category::where(
            'company_id',
            Auth::user()->company_id
        )
        ->findOrFail($request->id);

        /*
        |--------------------------------------------------------------------------
        | Check Duplicate Name
        |--------------------------------------------------------------------------
        */
        $categoryName = strtoupper(preg_replace('/\s+/', ' ',trim($request->category_name)));
        $exists = Category::where(
            'company_id',
            Auth::user()->company_id
        )
        ->where('category_name',$categoryName)
        ->where(
            'id',
            '!=',
            $category->id
        )
        ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'category_code' => [
                        'Kode kategori sudah digunakan'
                    ]
                ]
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        $category->category_name = $request->category_name;
        $category->description = $request->description;
        $category->status = $request->status;

        $category->save();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui'
        ]);
    }

    /**
     * Delete Category
     */
    public function destroy($id)
    {
        $category = Category::where(
            'company_id',
            Auth::user()->company_id
        )
        ->findOrFail($id);

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dihapus'
        ]);
    }

    /**
     * Get Sub Categories
     */
    public function subCategories($categoryId)
    {
        $companyId = Auth::user()->company_id;

        $category = Category::where('company_id', $companyId)
            ->findOrFail($categoryId);

        $subCategories = SubCategory::where('company_id', $companyId)
            ->where('category_id', $categoryId)
            ->withCount('assets')
            ->latest('id')
            ->paginate(10);

        return response()->json([
            'success' => true,

            'category' => [
                'id' => $category->id,
                'category_name' => $category->category_name,
            ],

            'data' => $subCategories->items(),

            'meta' => [
                'current_page' => $subCategories->currentPage(),
                'per_page' => $subCategories->perPage(),
                'total' => $subCategories->total(),
                'last_page' => $subCategories->lastPage(),
            ],
        ]);
    }
    public function list()
    {
        $categories = Category::where(
            'company_id',
            Auth::user()->company_id
        )
        ->where('status', 1)
        ->orderBy('category_name')
        ->get([
            'id',
            'category_name'
        ]);

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Store Sub Category
     */
    public function storeSubCategory(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'category_id' => 'required',
                'sub_category_name' => 'required',
                'status' => 'required',
            ],
            [
                'category_id.required' => 'Category wajib dipilih',
                'sub_category_name.required' => 'Nama sub category wajib diisi',
                'status.required' => 'Status wajib dipilih',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $companyId = Auth::user()->company_id;

        /*
        |--------------------------------------------------------------------------
        | Pastikan Category milik company user
        |--------------------------------------------------------------------------
        */

        $category = Category::where('company_id', $companyId)
            ->findOrFail($request->category_id);

        /*
        |--------------------------------------------------------------------------
        | Generate Code
        |--------------------------------------------------------------------------
        */

        $code = CodeHelper::generateNumber(
            //$request->sub_category_name,
            'SUBCAT-',
            SubCategory::class,
            'sub_category_code',
            $companyId
        );

        /*
        |--------------------------------------------------------------------------
        | Create
        |--------------------------------------------------------------------------
        */

        SubCategory::create([
            'company_id' => $companyId,
            'category_id' => $category->id,
            'sub_category_name' => strtoupper(trim($request->sub_category_name)),
            'sub_category_code' => $code,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sub Category berhasil ditambahkan'
        ]);
    }


    /**
     * Edit Sub Category
     */
    public function editSubCategory($id)
    {
        $subCategory = SubCategory::where(
            'company_id',
            Auth::user()->company_id
        )
        ->findOrFail($id);

        return response()->json($subCategory);
    }


    /**
     * Update Sub Category
     */
    public function updateSubCategory(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'id' => 'required',
                'sub_category_name' => 'required',
                'status' => 'required',
            ],
            [
                'sub_category_name.required' => 'Nama sub category wajib diisi',
                'status.required' => 'Status wajib dipilih',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $companyId = Auth::user()->company_id;

        $subCategory = SubCategory::where(
            'company_id',
            $companyId
        )->findOrFail($request->id);

        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        $subCategory->sub_category_name = strtoupper(
            trim($request->sub_category_name)
        );

        $subCategory->category_id = $request->category_id;
        $subCategory->description = $request->description;
        $subCategory->status = $request->status;

        $subCategory->save();

        return response()->json([
            'success' => true,
            'message' => 'Sub Category berhasil diperbarui'
        ]);
    }


    /**
     * Delete Sub Category
     */
    public function destroySubCategory($id)
    {
        $subCategory = SubCategory::where(
            'company_id',
            Auth::user()->company_id
        )->findOrFail($id);

        $subCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sub Category berhasil dihapus'
        ]);
    }

    /**
 * Export Excel
 */
    public function export()
    {
        /*
        |--------------------------------------------------------------------------
        | Get Category
        |--------------------------------------------------------------------------
        */

        $categories = Category::select(
            'categories.*',
            'companies.company_name'
        )

        ->leftJoin(
            'companies',
            'companies.id',
            '=',
            'categories.company_id'
        )

        ->withCount('assets')

        ->where(
            'categories.company_id',
            Auth::user()->company_id
        )

        ->orderBy(
            'categories.id'
        )

        ->get();


        /*
        |--------------------------------------------------------------------------
        | Spreadsheet
        |--------------------------------------------------------------------------
        */

        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();


        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue('A1', 'Company Name');
        $sheet->setCellValue('B1', 'Category Name');
        $sheet->setCellValue('C1', 'Category Code');
        $sheet->setCellValue('D1', 'Description');
        $sheet->setCellValue('E1', 'Total Asset');
        $sheet->setCellValue('F1', 'Status');


        /*
        |--------------------------------------------------------------------------
        | Styling Header
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle('A1:F1')
            ->getFont()
            ->setBold(true);


        /*
        |--------------------------------------------------------------------------
        | Auto Size
        |--------------------------------------------------------------------------
        */

        foreach (range('A', 'F') as $column) {

            $sheet->getColumnDimension($column)
                ->setAutoSize(true);
        }


        /*
        |--------------------------------------------------------------------------
        | Data
        |--------------------------------------------------------------------------
        */

        $row = 2;

        foreach ($categories as $item) {

            $status = $item->status == 1
                ? 'Active'
                : 'Non Active';


            $sheet->setCellValue(
                'A' . $row,
                $item->company_name
            );

            $sheet->setCellValue(
                'B' . $row,
                $item->category_name
            );

            $sheet->setCellValue(
                'C' . $row,
                $item->category_code
            );

            $sheet->setCellValue(
                'D' . $row,
                $item->description
            );

            $sheet->setCellValue(
                'E' . $row,
                $item->assets_count
            );

            $sheet->setCellValue(
                'F' . $row,
                $status
            );


            $row++;
        }


        /*
        |--------------------------------------------------------------------------
        | Download
        |--------------------------------------------------------------------------
        */

        $writer = new Xlsx($spreadsheet);


        $filename = 'Category_' . date('Ymd_His') . '.xlsx';


        $temp_file = tempnam(
            sys_get_temp_dir(),
            'categories'
        );


        $writer->save($temp_file);


        return response()->download(
            $temp_file,
            $filename
        )->deleteFileAfterSend(true);
    }
}