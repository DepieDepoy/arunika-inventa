<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Role & permission (role.view / role.create / role.edit / role.delete / role.permission).
 *
 * Web memakai ID terenkripsi; API memakai ID biasa yang selalu dibatasi
 * company_id. Role "administrator" bersifat sistem: tidak dapat diubah,
 * dihapus, maupun diatur permission-nya (Administrator selalu punya semua akses).
 */
class RoleController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $roles = Role::query()
            ->where('company_id', $request->user()->company_id)
            ->withCount(['users', 'permissions'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->integer('status')))
            ->when($request->filled('search'), fn ($q) => $q->where('role_name', 'like', '%' . trim((string) $request->query('search')) . '%'))
            ->orderBy('role_name')
            ->get()
            ->map(fn (Role $r) => $this->format($r));

        return $this->ok(['roles' => $roles]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $role = $this->findRole($request, $id)->loadCount(['users', 'permissions']);

        return $this->ok([
            'role' => $this->format($role),
            'permission_ids' => $role->role_code === 'administrator'
                ? Permission::query()->pluck('id')
                : $role->permissions()->pluck('permissions.id'),
        ]);
    }

    /**
     * Katalog seluruh permission, dikelompokkan per modul.
     */
    public function permissions(): JsonResponse
    {
        $modules = Permission::query()
            ->orderBy('module')
            ->orderBy('id')
            ->get(['id', 'permission_name', 'permission_code', 'module', 'action', 'description'])
            ->groupBy('module')
            ->map(fn ($items, $module) => [
                'module' => $module,
                'permissions' => $items->map(fn (Permission $p) => [
                    'id' => $p->id,
                    'code' => $p->permission_code,
                    'name' => $p->permission_name,
                    'action' => $p->action,
                    'description' => $p->description,
                ])->values(),
            ])
            ->values();

        return $this->ok(['modules' => $modules]);
    }

    /**
     * Body: role_name, status (0|1)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:0,1'],
        ]);

        $companyId = $request->user()->company_id;
        $roleCode = Str::slug(strtolower($validated['role_name']), '_');

        if ($roleCode === '' || $roleCode === 'administrator') {
            return $this->fail('Nama role tidak dapat digunakan.', 'VALIDATION_ERROR', 422, [
                'role_name' => ['Nama role tidak dapat digunakan.'],
            ]);
        }

        if (Role::withTrashed()->where('company_id', $companyId)->where('role_code', $roleCode)->exists()) {
            return $this->fail('Role sudah terdaftar.', 'VALIDATION_ERROR', 422, [
                'role_name' => ['Role sudah terdaftar.'],
            ]);
        }

        $role = Role::create([
            'company_id' => $companyId,
            'role_name' => $validated['role_name'],
            'role_code' => $roleCode,
            'status' => (int) $validated['status'],
            'user_add' => $request->user()->id,
        ]);

        return $this->ok(['role' => $this->format($role->loadCount(['users', 'permissions']))], 'Role berhasil ditambahkan.', 201);
    }

    /**
     * Body: role_name, status (0|1)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $role = $this->findRole($request, $id);

        if ($denied = $this->guardSystemRole($role)) {
            return $denied;
        }

        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:0,1'],
        ]);

        $role->update([
            'role_name' => $validated['role_name'],
            'status' => (int) $validated['status'],
        ]);

        return $this->ok(['role' => $this->format($role->loadCount(['users', 'permissions']))], 'Role berhasil diperbarui.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $role = $this->findRole($request, $id);

        if ($denied = $this->guardSystemRole($role)) {
            return $denied;
        }

        if ($role->users()->exists()) {
            return $this->fail('Role tidak dapat dihapus karena masih digunakan oleh user.', 'ROLE_IN_USE');
        }

        $role->delete();

        return $this->ok(null, 'Role berhasil dihapus.');
    }

    /**
     * Body: permission_ids: int[]  (daftar lengkap; yang tidak dikirim dicabut)
     */
    public function syncPermissions(Request $request, int $id): JsonResponse
    {
        $role = $this->findRole($request, $id);

        if ($denied = $this->guardSystemRole($role)) {
            return $denied;
        }

        $validated = $request->validate([
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['integer'],
        ]);

        $permissionIds = Permission::query()
            ->whereIn('id', $validated['permission_ids'])
            ->pluck('id')
            ->all();

        $before = $role->permissions()->pluck('permission_code')->sort()->values()->all();

        $role->permissions()->sync($permissionIds);

        $after = Permission::query()->whereIn('id', $permissionIds)->orderBy('permission_code')->pluck('permission_code')->all();
        $added = array_values(array_diff($after, $before));
        $removed = array_values(array_diff($before, $after));

        if ($added !== [] || $removed !== []) {
            AuditLogger::log(
                event: 'permission_updated',
                module: 'role',
                description: 'Mengubah permission role "' . $role->role_name . '"'
                    . ' (+' . count($added) . ' / -' . count($removed) . ')',
                subject: $role,
                oldValues: ['removed' => $removed],
                newValues: ['added' => $added],
            );
        }

        return $this->ok([
            'role' => $this->format($role->loadCount(['users', 'permissions'])),
            'permission_ids' => $permissionIds,
            'added' => $added,
            'removed' => $removed,
        ], 'Permission berhasil disimpan.');
    }

    protected function findRole(Request $request, int $id): Role
    {
        return Role::query()
            ->where('company_id', $request->user()->company_id)
            ->findOrFail($id);
    }

    protected function guardSystemRole(Role $role): ?JsonResponse
    {
        if ($role->role_code === 'administrator') {
            return $this->fail('Role Administrator adalah role sistem dan tidak dapat diubah.', 'SYSTEM_ROLE', 403);
        }

        // Cegah eskalasi hak akses: non-admin tidak boleh mengubah role miliknya sendiri.
        $user = request()->user();

        if (!$user->isAdministrator() && (int) $user->role_id === (int) $role->id) {
            return $this->fail('Anda tidak dapat mengubah role yang sedang Anda gunakan.', 'CANNOT_MODIFY_SELF', 403);
        }

        return null;
    }

    protected function format(Role $r): array
    {
        return [
            'id' => $r->id,
            'name' => $r->role_name,
            'code' => $r->role_code,
            'status' => (int) $r->status,
            'is_system' => $r->role_code === 'administrator',
            'users_count' => $r->users_count ?? null,
            'permissions_count' => $r->role_code === 'administrator' ? null : ($r->permissions_count ?? null),
        ];
    }
}
