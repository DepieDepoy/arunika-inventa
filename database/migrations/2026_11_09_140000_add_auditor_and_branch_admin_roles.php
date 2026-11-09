<?php

use App\Support\DefaultRoles;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Role bawaan baru "Auditor" (audit fisik aset) untuk semua perusahaan.
 *
 * Catatan: tidak ada role khusus cabang. Role sama untuk semua cabang;
 * cakupan data ditentukan oleh cabang user (lihat BranchAccess).
 * Role yang sudah ada (kode sama) tidak diubah permission-nya.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('companies')->pluck('id') as $companyId) {
            DefaultRoles::provision((int) $companyId);
        }
    }

    public function down(): void
    {
        $roleIds = DB::table('roles')
            ->where('role_code', DefaultRoles::AUDITOR)
            ->whereNotIn('id', DB::table('users')->whereNotNull('role_id')->pluck('role_id'))
            ->pluck('id');

        DB::table('role_permissions')->whereIn('role_id', $roleIds)->delete();
        DB::table('roles')->whereIn('id', $roleIds)->delete();
    }
};
