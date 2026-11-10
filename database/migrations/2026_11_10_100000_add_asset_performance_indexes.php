<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Index performa untuk data aset besar (ratusan ribu - jutaan baris).
 *
 *  - assets_stats_index : "covering index" untuk hitungan dashboard, daftar
 *    aset, kuota, laporan. COUNT/SUM cukup membaca index ramping ini, tidak
 *    perlu membaca seluruh baris tabel aset.
 *  - assets_search_fulltext : pencarian aset (nama, kode, merek, nomor seri)
 *    memakai index teks, bukan LIKE '%...%' yang membaca semua baris.
 *  - asset_audit_items (asset_audit_id, expected_location_id) : daftar
 *    "Tugas saya" & tandai massal per lokasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!$this->isMysql()) {
            return;
        }

        if (!$this->hasIndex('assets', 'assets_stats_index')) {
            DB::statement('ALTER TABLE assets ADD INDEX assets_stats_index
                (company_id, deleted_at, status, branch_id, location_id, responsible_user_id, category_id, asset_condition, created_at, disposed_at, purchase_price)');
        }

        if (!$this->hasIndex('assets', 'assets_search_fulltext')) {
            DB::statement('ALTER TABLE assets ADD FULLTEXT INDEX assets_search_fulltext (asset_name, asset_code, brand, serial_number)');
        }

        if (!$this->hasIndex('asset_audit_items', 'audit_items_location_index')) {
            DB::statement('ALTER TABLE asset_audit_items ADD INDEX audit_items_location_index (asset_audit_id, expected_location_id, result)');
        }

        // Ringkasan audit (dihitung setiap scan): cukup membaca index.
        if (!$this->hasIndex('asset_audit_items', 'audit_items_summary_index')) {
            DB::statement('ALTER TABLE asset_audit_items ADD INDEX audit_items_summary_index (asset_audit_id, in_scope, result, location_mismatch, branch_mismatch, `condition`)');
        }

        // Jadwal maintenance & garansi yang akan habis (laporan, pengingat harian).
        if (!$this->hasIndex('assets', 'assets_next_maintenance_index')) {
            DB::statement('ALTER TABLE assets ADD INDEX assets_next_maintenance_index (company_id, next_maintenance_date, status, deleted_at, created_at, disposed_at)');
        }

        if (!$this->hasIndex('assets', 'assets_warranty_end_index')) {
            DB::statement('ALTER TABLE assets ADD INDEX assets_warranty_end_index (company_id, warranty_end, status, deleted_at, created_at, disposed_at)');
        }
    }

    public function down(): void
    {
        if (!$this->isMysql()) {
            return;
        }

        foreach ([['assets', 'assets_stats_index'], ['assets', 'assets_search_fulltext'], ['asset_audit_items', 'audit_items_location_index'], ['asset_audit_items', 'audit_items_summary_index'], ['assets', 'assets_next_maintenance_index'], ['assets', 'assets_warranty_end_index']] as [$table, $index]) {
            if ($this->hasIndex($table, $index)) {
                DB::statement("ALTER TABLE {$table} DROP INDEX {$index}");
            }
        }
    }

    private function isMysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn ($i) => $i['name'] === $index);
    }
};
