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

        $roles = Role::where('company_id', Auth::user()->company_id)
        ->where('status', 1)
        ->orderBy('role_name')
        ->get();

        return view(
            'dashboard.user.index',
            compact('users','roles')
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
                'name'     => 'required',
                'email'    => 'required|email',
                'phone'   => 'required',
                'role_id'  => 'required',
                //'password' => 'required|min:8|confirmed',
                'status'   => 'required',
            ],
            [
                'name.required'     => 'Nama wajib diisi',
                'email.required'    => 'Email wajib diisi',
                'email.email'       => 'Format email tidak valid',
                'phone.required'   => 'No. Telepon wajib diisi',
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
        | Cek Email&Phone Duplicate
        |--------------------------------------------------------------------------
        */

        if (
            User::where('email', $request->email)
                ->where('company_id', Auth::user()->company_id)
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
        if (
            User::where('phone', $request->phone)
                ->where('company_id', Auth::user()->company_id)
                ->exists()
        ) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'email' => [
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

        $role = Role::where('id', $request->role_id)
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
            'role_id'      => $request->role_id,
            'name'         => $request->name,
            'phone'       => $request->phone,
            'email'        => $request->email,
            'password'     => Hash::make($passwordPlain),
            'status'       => $request->status,
        ]);


        return response()->json([
            'success' => true,
            'message' => 'User berhasil ditambahkan',
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
                        '.e($row->role_name).'
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
                'name',
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
    public function edit($id)
    {
        $user = User::where(
            'id',
            Auth::user()->id
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
                'email'   => 'required|email',
                'phone'  => 'required',
                'role_id' => 'required',
                'status'  => 'required',
            ],
            [
                'name.required'    => 'Nama wajib diisi',
                'email.required'   => 'Email wajib diisi',
                'email.email'      => 'Format email tidak valid',
                'phone.required'  => 'No. Telepon wajib diisi',
                'role_id.required' => 'Role wajib dipilih',
                'status.required'  => 'Status wajib dipilih',
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
            User::where('email', $request->email)
                ->where('id', '!=', $user->id)
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

        $role = Role::where('id', $request->role_id)
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
        $user->email   = $request->email;
        $user->phone  = $request->phone;
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
                    'password.min' => 'Password minimal 8 karakter',
                    'password.confirmed' => 'Konfirmasi password tidak sesuai',
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
        $sheet->setCellValue('C1', 'Email');
        $sheet->setCellValue('D1', 'No. Telepon');
        $sheet->setCellValue('E1', 'Role');
        $sheet->setCellValue('F1', 'Role Code');
        $sheet->setCellValue('G1', 'Status');


        /*
        |--------------------------------------------------------------------------
        | Styling Header
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle('A1:G1')
            ->getFont()
            ->setBold(true);


        /*
        |--------------------------------------------------------------------------
        | Auto Size
        |--------------------------------------------------------------------------
        */

        foreach (range('A', 'G') as $column) {

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
                $item->email
            );

            $sheet->setCellValue(
                'D' . $row,
                $item->phone
            );

            $sheet->setCellValue(
                'E' . $row,
                $item->role_name
            );

            $sheet->setCellValue(
                'F' . $row,
                $item->role_code
            );

            $sheet->setCellValue(
                'G' . $row,
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
}