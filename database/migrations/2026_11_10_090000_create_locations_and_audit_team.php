<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MASTER LOKASI PER CABANG + TIM AUDIT
 *  - locations                 : lokasi di dalam cabang (Lantai 1, Gudang, Ruang Finance, ...)
 *  - assets.location_id        : lokasi aset (kolom teks assets.location tetap = nama lokasi)
 *  - asset_audit_items         : lokasi & cabang tercatat/ditemukan dalam bentuk ID + status beda cabang
 *  - asset_audit_assignees     : petugas audit (+ area lokasi tanggung jawab)
 *  - permission location.*
 *
 * Lokasi teks yang sudah ada otomatis dijadikan master di cabang asetnya.
 */
return new class extends Migration
{
    private array $permissions = [
        ['permission_code' => 'location.view', 'permission_name' => 'View Location', 'action' => 'view', 'description' => 'Melihat master lokasi (ruangan / area) per cabang'],
        ['permission_code' => 'location.create', 'permission_name' => 'Create Location', 'action' => 'create', 'description' => 'Menambah lokasi'],
        ['permission_code' => 'location.edit', 'permission_name' => 'Edit Location', 'action' => 'edit', 'description' => 'Mengubah lokasi'],
        ['permission_code' => 'location.delete', 'permission_name' => 'Delete Location', 'action' => 'delete', 'description' => 'Menghapus lokasi yang tidak dipakai aset'],
    ];

    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('description', 255)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'branch_id', 'status']);
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('location')
                ->constrained('locations')->nullOnDelete();
        });

        Schema::table('asset_audits', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('location_filter')
                ->constrained('locations')->nullOnDelete();
        });

        Schema::table('asset_audit_items', function (Blueprint $table) {
            $table->foreignId('expected_branch_id')->nullable()->after('expected_location')
                ->constrained('branches')->nullOnDelete();
            $table->foreignId('expected_location_id')->nullable()->after('expected_branch_id')
                ->constrained('locations')->nullOnDelete();
            $table->foreignId('found_location_id')->nullable()->after('found_location')
                ->constrained('locations')->nullOnDelete();
            // Aset milik cabang lain terbaca di audit ini.
            $table->boolean('branch_mismatch')->default(false)->after('location_mismatch');
        });

        Schema::create('asset_audit_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_audit_id')->constrained('asset_audits')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Area tanggung jawab; NULL = semua lokasi dalam cakupan audit.
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->timestamps();

            $table->unique(['asset_audit_id', 'user_id', 'location_id'], 'audit_assignee_unique');
        });

        $now = now();

        foreach ($this->permissions as $p) {
            DB::table('permissions')->updateOrInsert(
                ['permission_code' => $p['permission_code']],
                $p + ['module' => 'location', 'created_at' => $now, 'updated_at' => $now]
            );
        }

        /*
         * Lokasi teks lama -> master lokasi di cabang aset.
         */
        $rows = DB::table('assets')
            ->whereNotNull('location')
            ->whereRaw("TRIM(location) <> ''")
            ->whereNotNull('branch_id')
            ->select('company_id', 'branch_id', DB::raw('TRIM(location) AS name'))
            ->distinct()
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $key = $row->branch_id . '|' . mb_strtolower($row->name);

            if (isset($map[$key])) {
                continue;
            }

            $map[$key] = DB::table('locations')->insertGetId([
                'company_id' => $row->company_id,
                'branch_id' => $row->branch_id,
                'name' => $row->name,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (DB::table('assets')->whereNotNull('location')->whereNotNull('branch_id')->get(['id', 'branch_id', 'location']) as $asset) {
            $key = $asset->branch_id . '|' . mb_strtolower(trim((string) $asset->location));

            if (isset($map[$key])) {
                DB::table('assets')->where('id', $asset->id)->update(['location_id' => $map[$key], 'location' => trim($asset->location)]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('permission_code', array_column($this->permissions, 'permission_code'))->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        Schema::dropIfExists('asset_audit_assignees');

        Schema::table('asset_audit_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('found_location_id');
            $table->dropConstrainedForeignId('expected_location_id');
            $table->dropConstrainedForeignId('expected_branch_id');
            $table->dropColumn('branch_mismatch');
        });

        Schema::table('asset_audits', fn (Blueprint $table) => $table->dropConstrainedForeignId('location_id'));
        Schema::table('assets', fn (Blueprint $table) => $table->dropConstrainedForeignId('location_id'));

        Schema::dropIfExists('locations');
    }
};
