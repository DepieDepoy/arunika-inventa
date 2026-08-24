<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Helpers\CodeHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class VendorController extends Controller
{
    /**
     * Display vendor page
     */
    public function index()
    {
        return view('dashboard.vendor.index');
    }


    /**
     * DataTables
     */
    public function data(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $query = Vendor::where('company_id', $companyId)
            ->withCount('assets')
            ->latest();

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('vendor_name', function ($row) {
                return '
                    <div>
                        <div class="fw-bold text-dark">
                            ' . e($row->vendor_name) . '
                        </div>
                    </div>
                ';
            })
            ->editColumn('vendor_code', function ($row) {
                return '
                    <div>
                        <div class="text-dark">
                            ' . e($row->vendor_code) . '
                        </div>
                    </div>
                ';
            })
            ->editColumn('address', function ($row) {
                return '
                    <div>
                        <div class="text-dark">
                            ' . e($row->address) . '
                        </div>
                    </div>
                ';
            })
            ->editColumn('phone', function ($row) {
                return '
                    <div>
                        <div class="text-dark">
                            ' . e($row->phone) . '
                        </div>
                    </div>
                ';
            })
            ->editColumn('pic', function ($row) {
                return '
                    <div>
                        <div class="text-dark">
                            ' . e($row->pic_name) . '
                        </div>
                    </div>
                ';
            })
            
            ->editColumn('status', function ($row) {
                if ($row->status == 1) {
                    return '
                        <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2">
                            <i class="fa-solid fa-circle-check me-1 text-success"></i>
                            Active
                        </span>
                    ';
                }
                return '
                    <span class="badge rounded-pill bg-danger-subtle text-danger px-3 py-2">
                        <i class="fa-solid fa-circle-xmark me-1 text-danger"></i>
                        Inactive
                    </span>
                ';
            })
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
                'vendor_name',
                'vendor_code',
                'address',
                'phone',
                'pic',
                'status',
                'action'
            ])

            ->make(true);
    }


    /**
     * Store vendor
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'vendor_name' => 'required',
                'address' => 'required',
                'phone' => 'nullable|max:20',
                'pic_name' => 'nullable',
                'email' => 'required',
                'status' => 'required',
            ],
            [
                'vendor_name.required' => 'Nama vendor wajib diisi',
                'address.required' => 'Alamat vendor wajib diisi',
                'phone.required' => 'Phone wajib diisi',
                'pic_name.required' => 'PIC wajib diisi',
                'email.required' => 'Email wajib diisi',
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
        | Company
        |--------------------------------------------------------------------------
        */

        $companyId = Auth::user()->company_id;


        /*
        |--------------------------------------------------------------------------
        | Generate Vendor Code
        |--------------------------------------------------------------------------
        */

        $code = CodeHelper::generateNumber(
            'VD-',
            Vendor::class,
            'vendor_code',
            $companyId
        );


        /*
        |--------------------------------------------------------------------------
        | Cek Duplicate Code
        |--------------------------------------------------------------------------
        */

        if (
            Vendor::where('company_id', $companyId)
                ->where('vendor_code', $code)
                ->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor code ' . $code . ' sudah digunakan.'
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Create Vendor
        |--------------------------------------------------------------------------
        */

        Vendor::create([
            'company_id' => $companyId,
            'vendor_name' => strtoupper(trim($request->vendor_name)),
            'vendor_code' => $code,
            'address' => $request->address,
            'phone' => $request->phone,
            'pic_name' => $request->pic_name,
            'email' => $request->email,
            'description' => $request->description,
            'status' => $request->status,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,
            'message' => 'Vendor berhasil ditambahkan'
        ]);
    }


    /**
     * Edit vendor
     */
    public function edit($id)
    {
        $vendor = Vendor::where(
            'company_id',
            Auth::user()->company_id
        )
        ->findOrFail($id);
        return response()->json($vendor);
    }


    /**
     * Update vendor
     */
    public function update(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $validator = Validator::make(
            $request->all(),
            [
                'id' => 'required',
                'vendor_name' => 'required',
                'address' => 'required',
                'phone' => 'nullable|max:20',
                'pic_name' => 'nullable',
                'email' => 'required|email',
                'status' => 'required',
            ],
            [
                'id.required' => 'ID vendor tidak ditemukan',
                'vendor_name.required' => 'Nama vendor wajib diisi',
                'address.required' => 'Alamat vendor wajib diisi',
                'phone.required' => 'Phone wajib diisi',
                'pic_name.required' => 'PIC wajib diisi',
                'email.required' => 'Email wajib diisi',
                'email.email' => 'Format email tidak valid',
                'status.required' => 'Status wajib dipilih',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $vendor = Vendor::where('company_id', $companyId)
            ->where('id', $request->id)
            ->first();

        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor tidak ditemukan.'
            ], 404);
        }

        try {

            $vendor->update([
                'vendor_name' => strtoupper(trim($request->vendor_name)),
                'address' => $request->address,
                'phone' => $request->phone,
                'pic_name' => $request->pic_name,
                'email' => $request->email,
                'description' => $request->description,
                'status' => $request->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vendor berhasil diperbarui.'
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui vendor.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Delete vendor
     */
    public function destroy($id)
    {
        $companyId = Auth::user()->company_id;

        $vendor = Vendor::where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (!$vendor) {
            return response()->json([
                'status' => false,
                'message' => 'Vendor not found.'
            ], 404);
        }

        try {

            $vendor->delete();

            return response()->json([
                'status' => true,
                'message' => 'Vendor successfully deleted.'
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete vendor.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Export Vendor to Excel
     */
    public function export()
    {
        $companyId = Auth::user()->company_id;

        $vendors = Vendor::where('company_id', $companyId)
            ->withCount('assets')
            ->orderBy('vendor_name')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Vendor Code');
        $sheet->setCellValue('C1', 'Vendor Name');
        $sheet->setCellValue('D1', 'PIC');
        $sheet->setCellValue('E1', 'Phone');
        $sheet->setCellValue('F1', 'Address');
        $sheet->setCellValue('G1', 'Total Assets');
        $sheet->setCellValue('H1', 'Status');

        $row = 2;

        foreach ($vendors as $index => $vendor) {

            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $vendor->vendor_code);
            $sheet->setCellValue('C' . $row, $vendor->vendor_name);
            $sheet->setCellValue('D' . $row, $vendor->pic_name);
            $sheet->setCellValue('E' . $row, $vendor->phone);
            $sheet->setCellValue('F' . $row, $vendor->address);
            $sheet->setCellValue('G' . $row, $vendor->assets_count);
            $sheet->setCellValue(
                'H' . $row,
                $vendor->status == 1 ? 'Active' : 'Inactive'
            );

            $row++;
        }

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        $fileName = 'vendors_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(
            function () use ($writer) {
                $writer->save('php://output');
            },
            $fileName
        );
    }
}