<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AUDIT FISIK ASET (cek fisik / inventarisasi aset tetap)
 *  - asset_audits      : satu sesi audit (mis. "Audit akhir tahun 2026 - Lantai 2")
 *  - asset_audit_items : daftar aset yang harus dicek + hasil scan
 *  - permission asset_audit.*
 */
return new class extends Migration
{
    private array $permissions = [
        ['permission_code' => 'asset_audit.view', 'permission_name' => 'View Asset Audit', 'action' => 'view', 'description' => 'Melihat sesi & laporan audit fisik aset'],
        ['permission_code' => 'asset_audit.create', 'permission_name' => 'Manage Asset Audit', 'action' => 'create', 'description' => 'Membuat, menyelesaikan, dan membatalkan sesi audit fisik'],
        ['permission_code' => 'asset_audit.scan', 'permission_name' => 'Scan Asset Audit', 'action' => 'scan', 'description' => 'Scan QR / mencentang aset saat audit fisik'],
    ];

    public function up(): void
    {
        Schema::create('asset_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('audit_no', 40);
            $table->string('title', 150);
            $table->string('location_filter')->nullable();
            // in_progress | completed | cancelled
            $table->string('status', 20)->default('in_progress');
            $table->text('notes')->nullable();
            $table->boolean('locations_applied')->default(false);
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'audit_no']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('asset_audit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_audit_id')->constrained('asset_audits')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->string('asset_code', 50);
            $table->string('asset_name');
            $table->string('expected_location')->nullable();
            // false = aset ditemukan tetapi tidak ada di daftar audit ini
            $table->boolean('in_scope')->default(true);
            // NULL = belum dicek, found = ditemukan, missing = tidak ditemukan
            $table->string('result', 20)->nullable();
            $table->string('found_location')->nullable();
            // good | minor_damage | major_damage
            $table->string('condition', 20)->nullable();
            $table->boolean('location_mismatch')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['asset_audit_id', 'asset_id']);
            $table->index(['asset_audit_id', 'result']);
        });

        $now = now();

        foreach ($this->permissions as $p) {
            DB::table('permissions')->updateOrInsert(
                ['permission_code' => $p['permission_code']],
                $p + ['module' => 'asset_audit', 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('permission_code', array_column($this->permissions, 'permission_code'))->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        Schema::dropIfExists('asset_audit_items');
        Schema::dropIfExists('asset_audits');
    }
};
