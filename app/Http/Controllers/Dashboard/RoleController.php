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
                'role_name.required' => __('Role Name wajib diisi'),
                'status.required'    => __('Status wajib dipilih'),
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
        // Unik per perusahaan (termasuk role yang sudah dihapus, sesuai index DB).
        if (Role::withTrashed()->where('company_id', Auth::user()->company_id)->where('role_code', $roleCode)->exists()) {

            return response()->json([
                'success' => false,
                'errors' => [
                    'role_name' => [
                        __('Role sudah terdaftar')
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
        $query = Role::where(
                'company_id',
                Auth::user()->company_id
            )
            ->latest();

        return DataTables::of($query)
            ->addIndexColumn()

            ->editColumn('role_name', function ($row) {

                return '
                    <div>
                        <div class="fw-bold text-dark">
                            ' . $row->role_name . '
                        </div>
                        <!--<small class="text-muted">
                            ' . $row->role_code . '
                        </small>-->
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

                /*
                |--------------------------------------------------------------------------
                | Encrypt ID
                |--------------------------------------------------------------------------
                */

                $encryptedId = encryptId($row->id);

                $action = '
                    <div class="d-flex justify-content-center align-items-center gap-1">
                ';


                // =====================================================
                // MANAGE PERMISSION
                // =====================================================

                if ($user->hasPermission('role.permission')) {

                    $action .= '
                        <a href="' . route(
                            'roles.permission',
                            $encryptedId
                        ) . '"
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
                        data-id="' . $encryptedId . '"
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
                        data-id="' . $encryptedId . '"
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


    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        // Decode encrypted ID
        $id = decryptId($id);

        if (!$id) {
            abort(404);
        }

        // Cari berdasarkan ID asli
        $role = Role::where('company_id', Auth::user()->company_id)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json($role);
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'role_name' => 'required',
                'status'    => 'required',
            ]
        );

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }


        // Decode encrypted ID
        $id = decryptId($request->id);

        if (!$id) {

            return response()->json([
                'success' => false,
                'message' => __('ID Role tidak valid.')
            ], 422);
        }


        // Cari menggunakan ID asli
        $role = Role::where('company_id', Auth::user()->company_id)
            ->where('id', $id)
            ->firstOrFail();


        $role->role_name = $request->role_name;
        $role->status    = $request->status;

        $role->save();


        return response()->json([
            'success' => true
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        // Decode encrypted ID
        $id = decryptId($id);

        if (!$id) {
            abort(404);
        }


        $role = Role::where(
                'company_id',
                Auth::user()->company_id
            )
            ->where('id', $id)
            ->firstOrFail();


        // Cek apakah role masih digunakan oleh user
        if ($role->users()->exists()) {

            return response()->json([
                'success' => false,
                'message' => __('Role tidak dapat dihapus karena masih digunakan oleh user.')
            ], 422);
        }


        $role->delete();


        return response()->json([
            'success' => true,
            'message' => __('Role berhasil dihapus.')
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | EXPORT
    |--------------------------------------------------------------------------
    */

    public function export()
    {
        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->where('company_id', Auth::user()->company_id)
            ->orderBy('role_name')
            ->get();

        $excel = \App\Services\Reports\SpreadsheetExporter::forCurrentUser();
        $excel->table('Role', 'Daftar Role & Hak Akses', [
            ['key' => 'role_name', 'label' => 'Nama role', 'width' => 26],
            ['key' => 'role_code', 'label' => 'Kode', 'width' => 18],
            ['key' => 'users_count', 'label' => 'Jumlah user', 'type' => 'number', 'width' => 13],
            ['label' => 'Jumlah hak akses', 'type' => 'number', 'width' => 16, 'value' => fn ($r) => $r->role_code === 'administrator' ? null : $r->permissions_count],
            ['label' => 'Keterangan', 'width' => 30, 'value' => fn ($r) => $r->role_code === 'administrator' ? 'Semua hak akses' : ''],
            ['label' => 'Status', 'width' => 11, 'value' => fn ($r) => (int) $r->status === 1 ? 'Aktif' : 'Nonaktif'],
        ], $roles, $roles->count() . ' role');

        return $excel->download('Vasetra-Role-' . now()->format('Ymd-His'));
    }


    /*
    |--------------------------------------------------------------------------
    | PERMISSION
    |--------------------------------------------------------------------------
    */

    public function permission($role)
    {
        /*
        |--------------------------------------------------------------------------
        | $role dari URL adalah encrypted ID
        |--------------------------------------------------------------------------
        */

        $id = decryptId($role);

        if (!$id) {
            abort(404);
        }


        /*
        |--------------------------------------------------------------------------
        | Cari Role berdasarkan ID asli
        |--------------------------------------------------------------------------
        */

        $roleModel = Role::where(
                'company_id',
                Auth::user()->company_id
            )
            ->where('id', $id)
            ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Ambil role_code
        |--------------------------------------------------------------------------
        */

        $roleCode = $roleModel->role_code;


        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = DB::table('permissions')
            ->orderBy('permission_code')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Permission yang sudah dimiliki role
        |--------------------------------------------------------------------------
        */

        $rolePermissions = DB::table('role_permissions')
            ->where('role_code', $roleCode)
            ->pluck('permission_id')
            ->toArray();


        /*
        |--------------------------------------------------------------------------
        | Group permission
        |--------------------------------------------------------------------------
        */

        $groupedPermissions = [];


        foreach ($permissions as $permission) {

            $parts = explode(
                '.',
                $permission->permission_code
            );

            $menu = $parts[0];

            $action = $parts[1] ?? 'view';

            $groupedPermissions[$menu][$action] = $permission;
        }


        /*
        |--------------------------------------------------------------------------
        | Kirim roleCode ke view
        |--------------------------------------------------------------------------
        */

        return view(
            'dashboard.roles.permission',
            compact(
                'role',
                'roleCode',
                'groupedPermissions',
                'rolePermissions'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE PERMISSION
    |--------------------------------------------------------------------------
    */

    public function savePermission(
        Request $request,
        $role
    ) {

        /*
        |--------------------------------------------------------------------------
        | Decode encrypted ID
        |--------------------------------------------------------------------------
        */

        $id = decryptId($role);

        if (!$id) {

            abort(404);
        }


        /*
        |--------------------------------------------------------------------------
        | Cari Role
        |--------------------------------------------------------------------------
        */

        $roleModel = Role::where(
                'company_id',
                Auth::user()->company_id
            )
            ->where('id', $id)
            ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Ambil role_code
        |--------------------------------------------------------------------------
        */

        $roleCode = $roleModel->role_code;


        /*
        |--------------------------------------------------------------------------
        | Hapus permission lama
        |--------------------------------------------------------------------------
        */

        DB::table('role_permissions')
            ->where('role_code', $roleCode)
            ->delete();


        /*
        |--------------------------------------------------------------------------
        | Simpan permission baru
        |--------------------------------------------------------------------------
        */

        foreach (
            $request->permissions ?? []
            as $permissionId
        ) {

            DB::table('role_permissions')
                ->insert([
                    'role_code'    => $roleCode,
                    'permission_id' => $permissionId,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
        }


        return back()->with([
            'success' => true,
            'message' => __('Role permission berhasil disimpan')
        ]);
    }
}
