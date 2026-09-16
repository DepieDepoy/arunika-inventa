<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
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

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use App\Imports\UserImport;
use App\Imports\UserPreviewImport;
use App\Exports\UserImportTemplateExport;
use App\Services\ExcelPreviewService;
use App\Jobs\ProcessUserImport;
use App\Models\ImportHistory;
use App\Helpers\PlanLimitHelper;


class UserController extends Controller
{
    /**
     * Halaman User
     */
    public function index()
    {
        $users = User::where(
            'company_id',
            Auth::user()->company_id
        )
        ->where('status', 1)
        ->orderBy('name')
        ->get();

        $roles = Role::where(
            'company_id',
            Auth::user()->company_id
        )
        ->where('status', 1)
        ->orderBy('role_name')
        ->get();

        return view(
            'dashboard.user.index',
            compact('users', 'roles')
        );
    }


    /**
     * Store User
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name'    => 'required',
                'nik'     => 'required',
                'email'   => 'required|email',
                'phone'   => 'required',
                'role_id' => 'required',
                //'password' => 'required|min:8|confirmed',
                'status'  => 'required',
            ],
            [
                'name.required'     => 'Nama wajib diisi',
                'nik.required'      => 'NIK wajib diisi',
                'email.required'    => 'Email wajib diisi',
                'email.email'       => 'Format email tidak valid',
                'phone.required'    => 'No. Telepon wajib diisi',
                'role_id.required'  => 'Role wajib dipilih',
                //'password.required' => 'Password wajib diisi',
                //'password.min'      => 'Password minimal 8 karakter',
                //'password.confirmed'=> 'Konfirmasi password tidak sesuai',
                'status.required'   => 'Status wajib dipilih',
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
        | PLAN LIMIT - USER
        |--------------------------------------------------------------------------
        */

        if (!PlanLimitHelper::canAddUsers(1)) {

            $limit = PlanLimitHelper::maxUsers();
            $current = PlanLimitHelper::currentUsers();

            return response()->json([
                'success' => false,
                'errors' => [
                    'plan_limit' => [
                        "Batas user pada paket Anda adalah {$limit} user. " .
                        "Saat ini sudah terdapat {$current} user. " .
                        "Silakan upgrade subscription untuk menambah user."
                    ]
                ]
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Cek Email Duplicate
        |--------------------------------------------------------------------------
        */

        if (
            User::where('email', $request->email)
                ->where(
                    'company_id',
                    Auth::user()->company_id
                )
                ->exists()
        ) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'email' => [
                        'Email sudah terdaftar'
                    ]
                ]
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Cek Phone Duplicate
        |--------------------------------------------------------------------------
        */

        if (
            User::where('phone', $request->phone)
                ->where(
                    'company_id',
                    Auth::user()->company_id
                )
                ->exists()
        ) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'phone' => [
                        'No Phone sudah terdaftar'
                    ]
                ]
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Cek Role
        |--------------------------------------------------------------------------
        */

        $role = Role::where(
            'id',
            $request->role_id
        )
        ->where(
            'company_id',
            Auth::user()->company_id
        )
        ->first();

        if (!$role) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'role_id' => [
                        'Role tidak valid'
                    ]
                ]
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Create User
        |--------------------------------------------------------------------------
        */

        $passwordPlain = Str::password(
            length: 10
        );

        User::create([
            'company_id' => Auth::user()->company_id,
            'role_id'    => $request->role_id,
            'name'       => $request->name,
            'nik'        => $request->nik,
            'phone'      => $request->phone,
            'email'      => $request->email,
            'password'   => Hash::make($passwordPlain),
            'status'     => $request->status,
        ]);


        return response()->json([
            'success'  => true,
            'message'  => 'User berhasil ditambahkan',
            'password' => $passwordPlain
        ]);
    }


    /**
     * DataTables
     */
    public function data()
    {
        $query = User::query()
            ->select(
                'users.*',
                'companies.company_name',
                'roles.role_name'
            )
            ->leftJoin(
                'companies',
                'companies.id',
                '=',
                'users.company_id'
            )
            ->leftJoin(
                'roles',
                'roles.id',
                '=',
                'users.role_id'
            )
            ->where(
                'users.company_id',
                Auth::user()->company_id
            )
            ->latest('users.id');

        return DataTables::of($query)
            ->addIndexColumn()

            ->editColumn('name', function ($row) {
                return '
                    <div>
                        <div class="fw-bold text-dark">
                            ' . e($row->name) . '
                        </div>
                    </div>
                ';
            })

            ->editColumn('nik', function ($row) {
                return '
                    <div>
                        <div class="fw-bold text-dark">
                            ' . e($row->nik) . '
                        </div>
                    </div>
                ';
            })

            ->editColumn('email', function ($row) {
                return '
                    <span class="text-dark">
                        ' . e($row->email) . '
                    </span>
                ';
            })

            ->editColumn('phone', function ($row) {
                return '
                    <span class="text-dark">
                        ' . e($row->phone) . '
                    </span>
                ';
            })

            ->editColumn('companies', function ($row) {
                return '
                    <span class="text-dark">
                        ' . e($row->company_name) . '
                    </span>
                ';
            })

            ->editColumn('role_name', function ($row) {
                return '
                    <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2">
                        <i class="fa-solid fa-user-shield me-1"></i>
                        ' . e($row->role_name) . '
                    </span>
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

                /** @var User $user */
                $user = Auth::user();

                $action = '
                    <div class="d-flex justify-content-center align-items-center gap-1">
                ';

                if ($user->hasPermission('user.edit')) {

                    $action .= '
                        <a href="javascript:void(0)"
                        class="btn-action btn-edit"
                        data-id="' . $row->id . '"
                        title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    ';
                }

                if ($user->hasPermission('user.delete')) {

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
                'name',
                'nik',
                'email',
                'phone',
                'companies',
                'role_name',
                'status',
                'action'
            ])

            ->make(true);
    }


    /**
     * Edit User
     */
    public function edit(int $id)
    {
        $user = User::where(
            'company_id',
            Auth::user()->company_id
        )
        ->findOrFail($id);

        return response()->json($user);
    }


    /**
     * Update User
     */
    public function update(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'id'      => 'required',
                'name'    => 'required',
                'nik'     => 'required',
                'email'   => 'required|email',
                'phone'   => 'required',
                'role_id' => 'required',
                'status'  => 'required',
            ],
            [
                'name.required'     => 'Nama wajib diisi',
                'nik.required'      => 'NIK wajib diisi',
                'email.required'    => 'Email wajib diisi',
                'email.email'       => 'Format email tidak valid',
                'phone.required'    => 'No. Telepon wajib diisi',
                'role_id.required'  => 'Role wajib dipilih',
                'status.required'   => 'Status wajib dipilih',
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
        | Get User
        |--------------------------------------------------------------------------
        */

        $user = User::where(
            'company_id',
            Auth::user()->company_id
        )
        ->findOrFail($request->id);


        /*
        |--------------------------------------------------------------------------
        | Check Email Duplicate
        |--------------------------------------------------------------------------
        */

        if (
            User::where(
                'email',
                $request->email
            )
            ->where(
                'id',
                '!=',
                $user->id
            )
            ->where(
                'company_id',
                Auth::user()->company_id
            )
            ->exists()
        ) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'email' => [
                        'Email sudah digunakan'
                    ]
                ]
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Check Role
        |--------------------------------------------------------------------------
        */

        $role = Role::where(
            'id',
            $request->role_id
        )
        ->where(
            'company_id',
            Auth::user()->company_id
        )
        ->first();

        if (!$role) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'role_id' => [
                        'Role tidak valid'
                    ]
                ]
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        $user->name    = $request->name;
        $user->nik     = $request->nik;
        $user->email   = $request->email;
        $user->phone   = $request->phone;
        $user->role_id = $request->role_id;
        $user->status  = $request->status;


        /*
        |--------------------------------------------------------------------------
        | Update Password
        |--------------------------------------------------------------------------
        |
        | Password hanya diubah kalau user mengisi password baru.
        |
        */

        if ($request->filled('password')) {

            $passwordValidator = Validator::make(
                $request->all(),
                [
                    'password' => 'min:8|confirmed'
                ],
                [
                    'password.min' =>
                        'Password minimal 8 karakter',

                    'password.confirmed' =>
                        'Konfirmasi password tidak sesuai',
                ]
            );


            if ($passwordValidator->fails()) {

                return response()->json([
                    'success' => false,
                    'errors'  => $passwordValidator->errors()
                ], 422);
            }


            $user->password = Hash::make(
                $request->password
            );
        }


        $user->save();


        return response()->json([
            'success' => true,
            'message' => 'User berhasil diperbarui'
        ]);
    }


    /**
     * Delete User
     */
    public function destroy($id)
    {
        $user = User::where(
            'company_id',
            Auth::user()->company_id
        )
        ->findOrFail($id);


        $user->delete();


        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus'
        ]);
    }


    /**
     * Export Excel
     */
    public function export()
    {
        $users = User::select(
            'users.*',
            'companies.company_name',
            'users.role_name',
            'users.role_code'
        )

        ->leftJoin(
            'companies',
            'companies.id',
            '=',
            'users.company_id'
        )

        ->leftJoin(
            'users',
            'users.id',
            '=',
            'users.role_id'
        )

        ->where(
            'users.company_id',
            Auth::user()->company_id
        )

        ->orderBy(
            'users.id'
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
        $sheet->setCellValue('B1', 'Name');
        $sheet->setCellValue('C1', 'NIK');
        $sheet->setCellValue('D1', 'Email');
        $sheet->setCellValue('E1', 'No. Telepon');
        $sheet->setCellValue('F1', 'Role');
        $sheet->setCellValue('G1', 'Role Code');
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


        foreach ($users as $item) {

            $status = $item->status == 1
                ? 'Active'
                : 'Non Active';


            $sheet->setCellValue(
                'A' . $row,
                $item->company_name
            );

            $sheet->setCellValue(
                'B' . $row,
                $item->name
            );

            $sheet->setCellValue(
                'C' . $row,
                $item->nik
            );

            $sheet->setCellValue(
                'D' . $row,
                $item->email
            );

            $sheet->setCellValue(
                'E' . $row,
                $item->phone
            );

            $sheet->setCellValue(
                'F' . $row,
                $item->role_name
            );

            $sheet->setCellValue(
                'G' . $row,
                $item->role_code
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


        $filename = 'User_' . date('Ymd_His') . '.xlsx';


        $temp_file = tempnam(
            sys_get_temp_dir(),
            'users'
        );


        $writer->save($temp_file);


        return response()->download(
            $temp_file,
            $filename
        )->deleteFileAfterSend(true);
    }


    /**
     * Halaman Import User
     */
    public function import()
    {
        return view('dashboard.user.import');
    }


    /**
     * Download Template Import User
     */
    public function downloadImportTemplate()
    {
        return Excel::download(
            new UserImportTemplateExport,
            'user_import_template.xlsx'
        );
    }


    /**
     * Preview Import User
     */
    public function previewImport(Request $request)
    {
        $filePath = null;

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

            $fileName =
                uniqid('user_import_') . '.' .
                $file->getClientOriginalExtension();

            $filePath = $file->storeAs(
                'temp/user-import',
                $fileName
            );


            /*
            |--------------------------------------------------------------------------
            | Full path file
            |--------------------------------------------------------------------------
            */

            $fullPath = Storage::path(
                $filePath
            );


            /*
            |--------------------------------------------------------------------------
            | Preview Excel
            |--------------------------------------------------------------------------
            |
            | Tidak menggunakan Excel::toArray()
            |
            | Hanya membaca maksimal 100 data pertama.
            |
            */

            $previewService =
                new ExcelPreviewService();

            $preview =
                $previewService->preview(
                    $fullPath,
                    100
                );


            /*
            |--------------------------------------------------------------------------
            | PLAN LIMIT - USER IMPORT
            |--------------------------------------------------------------------------
            */

            $totalRows =
                (int) $preview['totalRows'];

            $maxUsers =
                PlanLimitHelper::maxUsers();

            $currentUsers =
                PlanLimitHelper::currentUsers();


            /*
            |--------------------------------------------------------------------------
            | 0 = unlimited
            |--------------------------------------------------------------------------
            */

            if (
                $maxUsers > 0 &&
                ($currentUsers + $totalRows) > $maxUsers
            ) {

                Storage::delete(
                    $filePath
                );

                $remaining =
                    max(
                        0,
                        $maxUsers - $currentUsers
                    );

                return back()
                    ->withInput()
                    ->withErrors([
                        'excel_file' =>
                            'Import user ditolak. ' .
                            'Paket Anda maksimal ' .
                            number_format($maxUsers) .
                            ' user. ' .
                            'Saat ini sudah ada ' .
                            number_format($currentUsers) .
                            ' user. ' .
                            'Sisa slot hanya ' .
                            number_format($remaining) .
                            ' user, sedangkan file berisi ' .
                            number_format($totalRows) .
                            ' data.'
                    ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Simpan file ke session
            |--------------------------------------------------------------------------
            */

            session([
                'user_import_file' =>
                    $filePath,

                'user_import_total_rows' =>
                    $preview['totalRows'],
            ]);


            /*
            |--------------------------------------------------------------------------
            | Return Preview
            |--------------------------------------------------------------------------
            */

            return view(
                'dashboard.user.import-preview',
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
                'User import preview gagal',
                [
                    'error' =>
                        $e->getMessage(),

                    'trace' =>
                        $e->getTraceAsString(),
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | Hapus temporary file jika preview gagal
            |--------------------------------------------------------------------------
            */

            if (!empty($filePath ?? null)) {

                Storage::delete(
                    $filePath
                );
            }


            return back()->with(
                'error',
                'File Excel gagal dibaca: ' .
                $e->getMessage()
            );
        }
    }


    /**
     * Simpan Import User ke Database
     */
    public function importStore(Request $request)
    {
        $filePath =
            session('user_import_file');

        $totalRows =
            session(
                'user_import_total_rows',
                0
            );


        /*
        |--------------------------------------------------------------------------
        | Check Session
        |--------------------------------------------------------------------------
        */

        if (!$filePath) {

            return redirect()
                ->route('users.import')
                ->with(
                    'error',
                    'Session file import sudah tidak tersedia.'
                );
        }


        try {

            /*
            |--------------------------------------------------------------------------
            | Check File
            |--------------------------------------------------------------------------
            */

            if (!Storage::exists($filePath)) {

                return redirect()
                    ->route('users.import')
                    ->with(
                        'error',
                        'File Excel tidak ditemukan.'
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | Current User
            |--------------------------------------------------------------------------
            */

            $user =
                Auth::user();


            /*
            |--------------------------------------------------------------------------
            | PLAN LIMIT - SECOND CHECK
            |--------------------------------------------------------------------------
            |
            | Preview sudah melakukan pengecekan.
            | Di sini kita cek ulang sebelum Job masuk queue.
            |
            */

            $totalRows =
                (int) $totalRows;

            $maxUsers =
                PlanLimitHelper::maxUsers();

            $currentUsers =
                PlanLimitHelper::currentUsers();


            if (
                $maxUsers > 0 &&
                ($currentUsers + $totalRows) > $maxUsers
            ) {

                Storage::delete(
                    $filePath
                );

                session()->forget([
                    'user_import_file',
                    'user_import_total_rows',
                ]);


                $remaining =
                    max(
                        0,
                        $maxUsers - $currentUsers
                    );


                return redirect()
                    ->route('users.import')
                    ->with(
                        'error',
                        'Import user dibatalkan. ' .
                        'Sisa slot user pada paket Anda hanya ' .
                        number_format($remaining) .
                        ' user.'
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | Create Import History
            |--------------------------------------------------------------------------
            */

            $history =
                ImportHistory::create([
                    'company_id' =>
                        $user->company_id,

                    'user_id' =>
                        $user->id,

                    'module' =>
                        'user',

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
            | Dispatch Queue
            |--------------------------------------------------------------------------
            */

            ProcessUserImport::dispatch(
                $history->id,
                $filePath
            );


            /*
            |--------------------------------------------------------------------------
            | Clear Session
            |--------------------------------------------------------------------------
            */

            session()->forget([
                'user_import_file',
                'user_import_total_rows',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Redirect
            |--------------------------------------------------------------------------
            */

            return redirect()
                ->route(
                    'import.history'
                )
                ->with(
                    'success',
                    'Import berhasil dimasukkan ke antrian. Proses akan berjalan di background.'
                );


        } catch (\Throwable $e) {

            Log::error(
                'Gagal membuat User Import Job',
                [
                    'error' =>
                        $e->getMessage(),

                    'trace' =>
                        $e->getTraceAsString(),
                ]
            );


            return back()
                ->with(
                    'error',
                    'Import gagal diproses: ' .
                    $e->getMessage()
                );
        }
    }


    /**
     * Import History
     */
    public function importHistory()
    {
        $histories =
            ImportHistory::where(
                'company_id',
                Auth::user()->company_id
            )
            ->where(
                'module',
                'user'
            )
            ->latest()
            ->paginate(20);

        return view(
            'dashboard.user.import-history',
            compact('histories')
        );
    }


    /**
     * Import History Progress
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
                'user'
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
                        $success + $failed;


                    $progress =
                        $total > 0
                            ? min(
                                round(
                                    ($processed / $total) * 100
                                ),
                                100
                            )
                            : 0;


                    /*
                    |--------------------------------------------------------------------------
                    | DURASI IMPORT
                    |--------------------------------------------------------------------------
                    */

                    $duration = null;


                    if ($history->started_at) {

                        // Jika masih processing, hitung sampai sekarang
                        $endTime =
                            $history->finished_at ??
                            now();


                        $seconds =
                            $history->started_at
                                ->diffInSeconds($endTime);


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

                            if ($remainingSeconds > 0) {

                                $duration .=
                                    ' ' .
                                    $remainingSeconds .
                                    ' detik';
                            }

                        } elseif ($minutes > 0) {

                            $duration =
                                $minutes .
                                ' menit';

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


                        // Kalau masih berjalan
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
                                    ->format(
                                        'd M Y H:i:s'
                                    )
                                : null,

                        'finished_at' =>
                            $history->finished_at
                                ? $history->finished_at
                                    ->format(
                                        'd M Y H:i:s'
                                    )
                                : null,

                        'duration' =>
                            $duration,
                    ];
                }
            );


        return response()->json(
            $data
        );
    }


    /**
     * Import History Detail
     */
    public function importHistoryDetail(int $id)
    {
        $history =
            ImportHistory::where(
                'company_id',
                Auth::user()->company_id
            )
            ->where(
                'module',
                'user'
            )
            ->findOrFail($id);


        return view(
            'dashboard.user.import-history-detail',
            compact('history')
        );
    }


    /**
     * Import History Errors
     */
    public function importHistoryErrors(
        Request $request,
        int $id
    ) {
        $history =
            ImportHistory::where(
                'company_id',
                Auth::user()->company_id
            )
            ->where(
                'module',
                'user'
            )
            ->findOrFail($id);


        $query =
            $history->errors()
                ->select([
                    'id',
                    'row_number',
                    'data',
                    'error_message',
                    'created_at',
                ]);


        return DataTables::of($query)

            ->addColumn(
                'row_display',
                function ($error) {

                    return $error->row_number
                        ?: '-';
                }
            )

            ->addColumn(
                'data_display',
                function ($error) {

                    if (empty($error->data)) {

                        return '
                            <span class="text-muted">
                                -
                            </span>
                        ';
                    }


                    $data =
                        is_array($error->data)
                            ? $error->data
                            : json_decode(
                                $error->data,
                                true
                            );


                    if (!is_array($data)) {

                        return e(
                            $error->data
                        );
                    }


                    return '
                        <pre class="mb-0 small" style="
                            max-width: 500px;
                            max-height: 120px;
                            overflow: auto;
                            white-space: pre-wrap;
                        ">' .
                        e(
                            json_encode(
                                $data,
                                JSON_PRETTY_PRINT |
                                JSON_UNESCAPED_UNICODE |
                                JSON_UNESCAPED_SLASHES
                            )
                        ) .
                        '</pre>
                    ';
                }
            )

            ->addColumn(
                'error_display',
                function ($error) {

                    return '
                        <span class="text-danger">' .
                        e(
                            $error->error_message
                        ) .
                        '</span>
                    ';
                }
            )

            ->rawColumns([
                'data_display',
                'error_display',
            ])

            ->orderColumn(
                'row_display',
                'row_number $1'
            )

            ->make(true);
    }
}
