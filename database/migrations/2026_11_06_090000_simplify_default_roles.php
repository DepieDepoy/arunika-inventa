<?php

use App\Support\DefaultRoles;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menyederhanakan role bawaan menjadi Administrator, Teknisi, Karyawan.
 *
 * Per perusahaan:
 *  - role "Staff" diganti nama menjadi "Karyawan" (ID tetap, user tidak berubah);
 *  - role "Teknisi" dibuat bila belum ada;
 *  - role lama "Manager" / "Supervisor" dihapus HANYA jika tidak dipakai user
 *    (bila masih dipakai, dibiarkan sebagai role kustom agar tidak ada user
 *    yang tiba-tiba kehilangan akses);
 *  - permission bawaan hanya diisi pada role yang permission-nya masih kosong.
 */
return new class extends Migration
{
    public function up(): void
    {
        $companyIds = DB::table('companies')->pluck('id');

        foreach ($companyIds as $companyId) {
            DB::transaction(function () use ($companyId) {
                $hasEmployee = DB::table('roles')
                    ->where('company_id', $companyId)
                    ->where('role_code', DefaultRoles::EMPLOYEE)
                    ->exists();

                if (!$hasEmployee) {
                    DB::table('roles')
                        ->where('company_id', $companyId)
                        ->where('role_code', 'staff')
                        ->update([
                            'role_name' => 'Karyawan',
                            'role_code' => DefaultRoles::EMPLOYEE,
                            'deleted_at' => null,
                            'updated_at' => now(),
                        ]);
                }

                DefaultRoles::provision($companyId);

                foreach (['manager', 'supervisor', 'staff'] as $legacyCode) {
                    $role = DB::table('roles')
                        ->where('company_id', $companyId)
                        ->where('role_code', $legacyCode)
                        ->first();

                    if (!$role || DB::table('users')->where('role_id', $role->id)->exists()) {
                        continue;
                    }

                    DB::table('role_permissions')->where('role_id', $role->id)->delete();
                    DB::table('roles')->where('id', $role->id)->delete();
                }
            });
        }
    }

    public function down(): void
    {
        // Hanya nama Karyawan yang dikembalikan menjadi Staff; role yang dihapus tidak dibuat ulang.
        DB::table('roles')
            ->where('role_code', DefaultRoles::EMPLOYEE)
            ->update(['role_name' => 'Staff', 'role_code' => 'staff', 'updated_at' => now()]);
    }
};
