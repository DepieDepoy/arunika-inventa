<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionController extends Controller
{
    public function index($role)
    {
        $companyId = Auth::user()->company_id;

        /*
        |--------------------------------------------------------------------------
        | Decode encrypted role ID
        |--------------------------------------------------------------------------
        */

        $roleId = decryptId($role);

        if (!$roleId) {
            abort(404);
        }


        /*
        |--------------------------------------------------------------------------
        | Cari role berdasarkan ID asli
        |--------------------------------------------------------------------------
        */

        $role = Role::where('company_id', $companyId)
            ->where('id', $roleId)
            ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Ambil permissions
        |--------------------------------------------------------------------------
        */

        $permissions = Permission::orderBy('module')
            ->orderBy('id')
            ->get()
            ->groupBy('module');


        /*
        |--------------------------------------------------------------------------
        | Permission yang dimiliki role
        |--------------------------------------------------------------------------
        */

        $rolePermissions = $role->permissions()
            ->pluck('permissions.id')
            ->toArray();


        return view(
            'dashboard.role.permission',
            compact(
                'role',
                'permissions',
                'rolePermissions'
            )
        );
    }


    public function savePermission(Request $request, $role)
    {
        $companyId = Auth::user()->company_id;

        /*
        |--------------------------------------------------------------------------
        | Decode encrypted role ID
        |--------------------------------------------------------------------------
        */

        $roleId = decryptId($role);

        if (!$roleId) {
            abort(404);
        }


        /*
        |--------------------------------------------------------------------------
        | Cari role berdasarkan ID asli
        |--------------------------------------------------------------------------
        */

        $roleModel = Role::where('company_id', $companyId)
            ->where('id', $roleId)
            ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Ambil permission IDs
        |--------------------------------------------------------------------------
        */

        $permissionIds = $request->input(
            'permissions',
            []
        );


        /*
        |--------------------------------------------------------------------------
        | Simpan permission
        |--------------------------------------------------------------------------
        */

        /*
         * Hanya permission yang benar-benar ada yang disimpan.
         */
        $permissionIds = Permission::query()
            ->whereIn('id', array_filter((array) $permissionIds, 'is_numeric'))
            ->pluck('id')
            ->all();

        $before = $roleModel->permissions()
            ->pluck('permission_code')
            ->sort()
            ->values()
            ->all();

        $roleModel->permissions()
            ->sync($permissionIds);

        $after = Permission::query()
            ->whereIn('id', $permissionIds)
            ->orderBy('permission_code')
            ->pluck('permission_code')
            ->all();

        $added = array_values(array_diff($after, $before));
        $removed = array_values(array_diff($before, $after));

        if ($added !== [] || $removed !== []) {
            AuditLogger::log(
                event: 'permission_updated',
                module: 'role',
                description: 'Mengubah permission role "' . $roleModel->role_name . '"'
                    . ' (+' . count($added) . ' / -' . count($removed) . ')',
                subject: $roleModel,
                oldValues: ['removed' => $removed],
                newValues: ['added' => $added],
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Redirect kembali menggunakan encrypted ID
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'roles.permission',
                encryptId($roleModel->id)
            )
            ->with(
                'success',
                'Permission berhasil disimpan.'
            );
    }
}
