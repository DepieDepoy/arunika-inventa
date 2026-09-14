<?php

namespace App\Http\Controllers\Dashboard;
use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use Yajra\DataTables\Facades\DataTables;


class RoleController extends Controller
{
    public function index()
    {
        return view('dashboard.role.index');
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'role_name' => 'required',
                'status'    => 'required',
            ],
            [
                'role_name.required' => 'Role Name wajib diisi',
                'status.required'    => 'Status wajib dipilih',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        // Generate role_code
        $roleCode = Str::slug(
            strtolower($request->role_name),
            '_'
        );

        // Cek duplicate
        if (Role::where('role_code', $roleCode)->exists()) {

            return response()->json([
                'success' => false,
                'errors' => [
                    'role_name' => [
                        'Role sudah terdaftar'
                    ]
                ]
            ], 422);
        }

        Role::create([
            'company_id' => Auth::user()->company_id,
            'role_name'  => $request->role_name,
            'role_code'  => $roleCode,
            'status'     => $request->status,
            'created_by' => Auth::id(),
        ]);
        return response()->json([
            'success' => true
        ]);
    }

    public function data()
    {
        $query = Role::where('company_id', Auth::user()->company_id)
            ->latest();
        return DataTables::of($query)
            ->addIndexColumn()

            ->editColumn('role_name', function ($row) {
                return '
                    <div>
                        <div class="fw-bold text-dark">'.$row->role_name.'</div>
                        <!--<small class="text-muted">'.$row->role_code.'</small>-->
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

                /** @var User $user */
                $user = Auth::user();

                $action = '
                    <div class="d-flex justify-content-center align-items-center gap-1">
                ';

                // =====================================================
                // MANAGE PERMISSION
                // =====================================================
                if ($user->hasPermission('role.permission')) {

                    $action .= '
                        <a href="' . route('roles.permission', $row->id) . '"
                        class="btn-action"
                        title="Permission">
                            <i class="fa-solid fa-key"></i>
                        </a>
                    ';
                }

                // =====================================================
                // EDIT
                // =====================================================
                if ($user->hasPermission('role.edit')) {

                    $action .= '
                        <a href="javascript:void(0)"
                        class="btn-action btn-edit"
                        data-id="' . $row->id . '"
                        title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    ';
                }

                // =====================================================
                // DELETE
                // =====================================================
                if ($user->hasPermission('role.delete')) {

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
                'role_name',
                'status',
                'action'
            ])

            ->make(true);
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);

        return response()->json($role);
    }

    public function update(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'role_name'   => 'required',
                'status' => 'required',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $role = Role::findOrFail($request->id);

        $role->role_name   = $request->role_name;
        $role->status = $request->status;

        $role->save();

        return response()->json([
            'success' => true
        ]);
    }

    public function destroy($id)
    {
        $role = Role::where('company_id', Auth::user()->company_id)
            ->where('id', $id)
            ->firstOrFail();

        // Cek apakah role masih digunakan oleh user
        if ($role->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Role tidak dapat dihapus karena masih digunakan oleh user.'
            ], 422);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil dihapus.'
        ]);
    }
    
    public function export()
    {
       $role = Role::select(
            'roles.*',
            'companies.company_name'
        )
        ->leftJoin('companies', 'companies.id', '=', 'roles.company_id')
        ->where(
            'roles.company_id',
            Auth::user()->company_id
        )
        ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'Companies Name');
        $sheet->setCellValue('B1', 'Role Name');
        $sheet->setCellValue('C1', 'Role Code');
        $sheet->setCellValue('D1', 'Status');

         // Styling Header
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)
                ->setAutoSize(true);
        }
        
        $row = 2;

        foreach ($role as $items) {
            if ($items->status == 1){$s = "Active";} else {$s = "Non Active";}
            $sheet->setCellValue('A'.$row, $items->company_name);
            $sheet->setCellValue('B'.$row, $items->role_name);
            $sheet->setCellValue('C'.$row, $items->role_code);
            $sheet->setCellValue('D'.$row, $s);

            $row++;
        }

        $writer = new Xlsx($spreadsheet);

        $filename = 'Role_'.date('Ymd_His').'.xlsx';

        $temp_file = tempnam(sys_get_temp_dir(), 'users');

        $writer->save($temp_file);

        return response()->download(
            $temp_file,
            $filename
        )->deleteFileAfterSend(true);
    }

    public function permission($role)
    {
        $permissions = DB::table('permissions')
            ->orderBy('permission_code')
            ->get();

        $rolePermissions = DB::table('role_permissions')
            ->where('role_code', $role)
            ->pluck('permission_id')
            ->toArray();

        $groupedPermissions = [];

        foreach ($permissions as $permission) {

            $parts = explode('.', $permission->permission_code);

            $menu = $parts[0];
            $action = $parts[1] ?? 'view';

            $groupedPermissions[$menu][$action] = $permission;
        }

        return view(
            'dashboard.roles.permission',
            compact(
                'role',
                'groupedPermissions',
                'rolePermissions'
            )
        );
    }

    public function savePermission(Request $request, $role)
    {
        DB::table('role_permissions')
            ->where('role_code', $role)
            ->delete();

        foreach ($request->permissions ?? [] as $permissionId) {
            DB::table('role_permissions')->insert([
                'role_code' => $role,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with([
            'success' => true,
            'message' => 'Role permission berhasil disimpan'
        ]);
    }
}