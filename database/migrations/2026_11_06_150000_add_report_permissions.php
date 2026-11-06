<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Hak akses menu Laporan. Administrator otomatis punya semua akses;
 * role lain bisa diberi lewat menu Role.
 */
return new class extends Migration
{
    private array $permissions = [
        ['permission_code' => 'report.view', 'permission_name' => 'View Report', 'action' => 'view', 'description' => 'Melihat & mencetak laporan aset mingguan / bulanan / tahunan'],
        ['permission_code' => 'report.export', 'permission_name' => 'Export Report', 'action' => 'export', 'description' => 'Mengunduh laporan dalam Excel dan PowerPoint'],
    ];

    public function up(): void
    {
        foreach ($this->permissions as $p) {
            DB::table('permissions')->updateOrInsert(
                ['permission_code' => $p['permission_code']],
                $p + ['module' => 'report', 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('permission_code', array_column($this->permissions, 'permission_code'))->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
