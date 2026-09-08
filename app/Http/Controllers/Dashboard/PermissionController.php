<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionController extends Controller
{
    public function index($role)
    {
        $companyId = Auth::user()->company_id;

        $role = Role::where('company_id', $companyId)
            ->where('id', $role)
            ->firstOrFail();

        $permissions = Permission::orderBy('module')
            ->orderBy('id')
            ->get()
            ->groupBy('module');

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

        $role = Role::where('company_id', $companyId)
            ->where('id', $role)
            ->firstOrFail();

        $permissionIds = $request->input('permissions', []);

        $role->permissions()->sync($permissionIds);

        return redirect()
            ->route('roles.permission', $role->id)
            ->with('success', 'Permission berhasil disimpan.');
    }
}