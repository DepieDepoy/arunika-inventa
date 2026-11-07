<?php

use App\Support\DefaultRoles;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Hak akses "Aset Saya" (asset.my) + melengkapi permission bawaan
 * role Karyawan & Teknisi yang sudah ada:
 *  - Karyawan : Aset Saya, ajukan maintenance, riwayat maintenance (aset sendiri)
 *  - Teknisi  : + Aset Saya
 *  - Administrator : semua permission
 * Permission yang sudah dicentang admin tidak dihapus, hanya ditambah.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['permission_code' => 'asset.my'],
            [
                'permission_name' => 'My Asset',
                'module' => 'asset',
                'action' => 'my',
                'description' => 'Melihat aset yang dipegang sendiri & mengajukan maintenance',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $definitions = DefaultRoles::definitions();
        $now = now();

        $roles = DB::table('roles')
            ->whereIn('role_code', array_keys($definitions))
            ->whereNull('deleted_at')
            ->get(['id', 'role_code']);

        foreach ($roles as $role) {
            $codes = $definitions[$role->role_code]['permissions'];

            $permissionIds = $codes === ['*']
                ? DB::table('permissions')->pluck('id')
                : DB::table('permissions')->whereIn('permission_code', $codes)->pluck('id');

            $existing = DB::table('role_permissions')->where('role_id', $role->id)->pluck('permission_id')->all();

            $rows = $permissionIds
                ->reject(fn ($id) => in_array($id, $existing))
                ->map(fn ($id) => ['role_id' => $role->id, 'permission_id' => $id, 'created_at' => $now, 'updated_at' => $now])
                ->values()
                ->all();

            if ($rows) {
                DB::table('role_permissions')->insert($rows);
            }
        }
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('permission_code', 'asset.my')->value('id');

        if ($id) {
            DB::table('role_permissions')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }
    }
};
