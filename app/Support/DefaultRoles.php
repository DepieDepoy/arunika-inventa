<?php

namespace App\Support;

use App\Models\Permission;
use Illuminate\Support\Facades\DB;

/**
 * Role bawaan setiap perusahaan.
 *
 * Role = hak akses di aplikasi, BUKAN jabatan. Vasetra hanya butuh tiga
 * peran sesuai alur aset:
 *  - Administrator : kelola semuanya (role sistem, selalu punya semua akses).
 *  - Teknisi       : menerima, mengambil, dan mengerjakan request maintenance.
 *  - Karyawan      : memegang aset dan membuat request maintenance/perbaikan.
 *
 * Perusahaan tetap bisa menambah role sendiri (mis. "Staf Gudang") dari menu Role.
 */
class DefaultRoles
{
    public const ADMINISTRATOR = 'administrator';
    public const TECHNICIAN = 'teknisi';
    public const EMPLOYEE = 'karyawan';

    /**
     * role_code => [nama, permission bawaan]. Administrator mendapat semua permission.
     */
    public static function definitions(): array
    {
        return [
            self::ADMINISTRATOR => [
                'name' => 'Administrator',
                'permissions' => ['*'],
            ],
            self::TECHNICIAN => [
                'name' => 'Teknisi',
                'permissions' => [
                    'asset.view',
                    'maintenance.view',
                    'maintenance.create',
                    'maintenance.edit',
                    'maintenance.history',
                ],
            ],
            self::EMPLOYEE => [
                'name' => 'Karyawan',
                'permissions' => [
                    'maintenance.create',
                ],
            ],
        ];
    }

    /**
     * Membuat role bawaan yang belum ada untuk perusahaan dan mengisi
     * permission bawaan pada role yang permission-nya masih kosong.
     *
     * @return array<string, int> role_code => role_id
     */
    public static function provision(int $companyId, ?int $createdBy = null): array
    {
        $ids = [];
        $now = now();

        foreach (self::definitions() as $code => $definition) {
            $role = DB::table('roles')
                ->where('company_id', $companyId)
                ->where('role_code', $code)
                ->first();

            if ($role && $role->deleted_at !== null) {
                DB::table('roles')->where('id', $role->id)->update(['deleted_at' => null, 'status' => 1, 'updated_at' => $now]);
            }

            $roleId = $role->id ?? DB::table('roles')->insertGetId([
                'company_id' => $companyId,
                'role_name' => $definition['name'],
                'role_code' => $code,
                'status' => 1,
                'user_add' => $createdBy,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            self::fillPermissionsIfEmpty($roleId, $definition['permissions']);

            $ids[$code] = $roleId;
        }

        return $ids;
    }

    /**
     * Permission bawaan hanya dipasang bila role belum diatur sama sekali,
     * agar pengaturan yang sudah dibuat admin tidak tertimpa.
     */
    public static function fillPermissionsIfEmpty(int $roleId, array $codes): void
    {
        if (DB::table('role_permissions')->where('role_id', $roleId)->exists()) {
            return;
        }

        $permissionIds = $codes === ['*']
            ? Permission::query()->pluck('id')
            : Permission::query()->whereIn('permission_code', $codes)->pluck('id');

        $now = now();

        DB::table('role_permissions')->insert(
            $permissionIds->map(fn ($id) => [
                'role_id' => $roleId,
                'permission_id' => $id,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }
}
