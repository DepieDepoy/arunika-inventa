<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Subcategory;
use App\Models\User;
use App\Models\Category;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use App\Helpers\CodeHelper;

class SubCategoryController extends Controller
{
    /**
     * Halaman User
     */
    public function index()
    {
        $users = SubCategory::where(
            'company_id',
            Auth::user()->company_id
        )
        ->where('status', 1)
        ->orderBy('sub_category_name')
        ->get();

        $category = Category::where('company_id', Auth::user()->company_id)
        ->where('status', 1)
        ->orderBy('category_name')
        ->get();

        return view(
            'dashboard.subcategory.index',
            compact('users','category')
        );
    }

    /**
     * Store Sub Category
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'category_id'       => 'required',
                'sub_category_name' => 'required',
                'status'            => 'required',
            ],
            [
                'category_id.required'       => 'Category wajib dipilih',
                'sub_category_name.required' => 'Nama Sub Category wajib diisi',
                'status.required'            => 'Status wajib dipilih',
            ]
        );


        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Company
        |--------------------------------------------------------------------------
        */

        $companyId = Auth::user()->company_id;


        /*
        |--------------------------------------------------------------------------
        | Cek Category
        |--------------------------------------------------------------------------
        |
        | Category harus milik company user yang sedang login
        |
        */

        $category = Category::where(
            'id',
            $request->category_id
        )
        ->where(
            'company_id',
            $companyId
        )
        ->first();


        if (!$category) {

            return response()->json([
                'success' => false,
                'errors' => [
                    'category_id' => [
                        'Category tidak valid'
                    ]
                ]
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Generate Sub Category Code
        |--------------------------------------------------------------------------
        */

        $code = CodeHelper::generate(
            $request->sub_category_name,
            SubCategory::class,
            'sub_category_code',
            $companyId
        );


        /*
        |--------------------------------------------------------------------------
        | Cek Duplicate Code
        |--------------------------------------------------------------------------
        |
        | Termasuk data yang sudah Soft Delete.
        |
        */

        $exists = SubCategory::withTrashed()
            ->where(
                'company_id',
                $companyId
            )
            ->where(
                'sub_category_code',
                $code
            )
            ->exists();


        if ($exists) {

            return response()->json([
                'success' => false,
                'errors' => [
                    'sub_category_name' => [
                        'Kode Sub Category ' . $code . ' sudah pernah digunakan.'
                    ]
                ]
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Create Sub Category
        |--------------------------------------------------------------------------
        */

        SubCategory::create([
            'company_id'        => $companyId,
            'category_id'       => $request->category_id,
            'sub_category_name' => strtoupper(
                trim($request->sub_category_name)
            ),
            'sub_category_code' => $code,
            'description'       => $request->description,
            'status'            => $request->status,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,
            'message' => 'Sub Category berhasil ditambahkan'
        ]);
    }


    /**
     * DataTables
     */
    public function data()
    {
        $query = SubCategory::query()
            ->select(
                'sub_categories.*',
                'categories.category_name',
                'categories.category_code'
            )
            ->withCount('assets')
            ->leftJoin(
                'categories',
                'categories.id',
                '=',
                'sub_categories.category_id'
            )
            ->where(
                'sub_categories.company_id',
                Auth::user()->company_id
            )
            ->latest('sub_categories.id');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('category_name', function ($row) {
                return '
                    <div class="fw-bold text-dark">
                        ' . e(ucwords(strtolower($row->category_name))) . '
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
            ->editColumn('sub_category_name', function ($row) {
                return '
                    <span class="fw-semibold text-dark">
                        ' . e(ucwords(strtolower($row->sub_category_name))) . '
                    </span>
                ';
            })
            ->editColumn('sub_category_code', function ($row) {
                return '
                    <span class="text-dark">
                        ' . e($row->sub_category_code) . '
                    </span>
                ';
            })
            ->editColumn('description', function ($row) {
                return '
                    <span class="text-dark">
                        ' . e($row->description ?? '-') . '
                    </span>
                ';
            })
            /*
            |--------------------------------------------------------------------------
            | Total Asset
            |--------------------------------------------------------------------------
            */
            ->addColumn('total_asset', function ($row) {
                 return '
                    <span class="text-dark">
                        ' . $row->assets_count . '
                    </span>
                ';
            })

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | Action
            |--------------------------------------------------------------------------
            */

            ->addColumn('action', function ($row) {

                return '
                    <div class="d-flex justify-content-center align-items-center gap-1">

                        <a href="javascript:void(0)"
                        class="btn-action btn-edit"
                        data-id="' . $row->id . '"
                        title="Edit">

                            <i class="fa-solid fa-pen-to-square"></i>

                        </a>

                        <a href="javascript:void(0)"
                        class="btn-action btn-delete"
                        data-id="' . $row->id . '"
                        title="Delete">

                            <i class="fa-solid fa-trash"></i>

                        </a>

                    </div>
                ';
            })

            ->rawColumns([
                'category_name',
                'category_code',
                'sub_category_name',
                'sub_category_code',
                'description',
                'total_asset',
                'status',
                'action'
            ])

            ->make(true);
    }


    /**
     * Edit User
     */
    public function edit($id)
    {
        $subcategory = SubCategory::where(
            'company_id',
            Auth::user()->company_id
        )
        ->findOrFail($id);

        return response()->json($subcategory);
    }

    /**
     * Update Sub Category
     */
    public function update(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'id'                => 'required',
                'category_id'       => 'required',
                'sub_category_name' => 'required',
                'status'            => 'required',
            ],
            [
                'category_id.required'       => 'Category wajib dipilih',
                'sub_category_name.required' => 'Nama Sub Category wajib diisi',
                'status.required'            => 'Status wajib dipilih',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Get Sub Category
        |--------------------------------------------------------------------------
        */

        $subcategory = SubCategory::where(
            'company_id',
            Auth::user()->company_id
        )
        ->findOrFail($request->id);


        /*
        |--------------------------------------------------------------------------
        | Check Category
        |--------------------------------------------------------------------------
        |
        | Pastikan category yang dipilih memang milik company user
        |
        */

        $category = Category::where(
            'id',
            $request->category_id
        )
        ->where(
            'company_id',
            Auth::user()->company_id
        )
        ->first();

        if (!$category) {

            return response()->json([
                'success' => false,
                'errors' => [
                    'category_id' => [
                        'Category tidak valid'
                    ]
                ]
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Check Duplicate Sub Category Code
        |--------------------------------------------------------------------------
        */

        if (
            SubCategory::where(
                'company_id',
                Auth::user()->company_id
            )
            ->where(
                'sub_category_code',
                $request->sub_category_code
            )
            ->where(
                'id',
                '!=',
                $subcategory->id
            )
            ->exists()
        ) {

            return response()->json([
                'success' => false,
                'errors' => [
                    'sub_category_code' => [
                        'Kode Sub Category sudah digunakan'
                    ]
                ]
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        $subcategory->category_id       = $request->category_id;
        $subcategory->sub_category_name = strtoupper(
            trim($request->sub_category_name)
        );
        $subcategory->description       = $request->description;
        $subcategory->status            = $request->status;


        $subcategory->save();


        return response()->json([
            'success' => true,
            'message' => 'Sub Category berhasil diperbarui'
        ]);
    }


    /**
     * Delete Sub Category
     */
    public function destroy($id)
    {
        $subcategory = SubCategory::where(
            'company_id',
            Auth::user()->company_id
        )
        ->findOrFail($id);


        $subcategory->delete();


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
        $subcategories = SubCategory::select(
            'sub_categories.*',
            'companies.company_name',
            'categories.category_name',
            'categories.category_code'
        )

        ->leftJoin(
            'companies',
            'companies.id',
            '=',
            'sub_categories.company_id'
        )

        ->leftJoin(
            'categories',
            'categories.id',
            '=',
            'sub_categories.category_id'
        )

        ->where(
            'sub_categories.company_id',
            Auth::user()->company_id
        )

        ->orderBy(
            'sub_categories.id'
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
        $sheet->setCellValue('D1', 'Sub Category Name');
        $sheet->setCellValue('E1', 'Sub Category Code');
        $sheet->setCellValue('F1', 'Description');
        $sheet->setCellValue('G1', 'Total Asset');
        $sheet->setCellValue('H1', 'Status');


        /*
        |--------------------------------------------------------------------------
        | Styling Header
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle('A1:H1')
            ->getFont()
            ->setBold(true);


        /*
        |--------------------------------------------------------------------------
        | Auto Size
        |--------------------------------------------------------------------------
        */

        foreach (range('A', 'H') as $column) {

            $sheet->getColumnDimension($column)
                ->setAutoSize(true);
        }


        /*
        |--------------------------------------------------------------------------
        | Data
        |--------------------------------------------------------------------------
        */

        $row = 2;


        foreach ($subcategories as $item) {

            $status = $item->status == 1
                ? 'Active'
                : 'Non Active';


            $totalAsset = $item->assets()
                ->count();


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
                $item->sub_category_name
            );

            $sheet->setCellValue(
                'E' . $row,
                $item->sub_category_code
            );

            $sheet->setCellValue(
                'F' . $row,
                $item->description
            );

            $sheet->setCellValue(
                'G' . $row,
                $totalAsset
            );

            $sheet->setCellValue(
                'H' . $row,
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


        $filename = 'Sub_Category_' . date('Ymd_His') . '.xlsx';


        $temp_file = tempnam(
            sys_get_temp_dir(),
            'subcategories'
        );


        $writer->save($temp_file);


        return response()->download(
            $temp_file,
            $filename
        )->deleteFileAfterSend(true);
    }
}
