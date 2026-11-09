<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MULTI-CABANG
 *  - branches                : daftar cabang per perusahaan (selalu ada 1 Kantor Pusat)
 *  - assets.branch_id        : aset milik cabang mana
 *  - users.branch_id         : NULL = melihat semua cabang; terisi = hanya cabang itu
 *  - plans.max_branches      : batas cabang per paket (NULL = tanpa batas)
 *  - branch_transfers(+items): mutasi aset antar-cabang (kirim -> terima)
 *  - permission branch.*
 */
return new class extends Migration
{
    private array $permissions = [
        ['permission_code' => 'branch.view', 'permission_name' => 'View Branch', 'action' => 'view', 'description' => 'Melihat daftar cabang'],
        ['permission_code' => 'branch.create', 'permission_name' => 'Create Branch', 'action' => 'create', 'description' => 'Menambah cabang (mengikuti batas paket)'],
        ['permission_code' => 'branch.edit', 'permission_name' => 'Edit Branch', 'action' => 'edit', 'description' => 'Mengubah data cabang'],
        ['permission_code' => 'branch.delete', 'permission_name' => 'Delete Branch', 'action' => 'delete', 'description' => 'Menghapus cabang yang sudah kosong'],
        ['permission_code' => 'branch.transfer', 'permission_name' => 'Transfer Asset', 'action' => 'transfer', 'description' => 'Membuat & membatalkan mutasi aset antar-cabang'],
        ['permission_code' => 'branch.receive', 'permission_name' => 'Receive Transfer', 'action' => 'receive', 'description' => 'Menerima mutasi aset di cabang tujuan'],
    ];

    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('branch_code', 30);
            $table->string('branch_name', 150);
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('pic_name', 150)->nullable();
            $table->boolean('is_head_office')->default(false);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'branch_code']);
            $table->index(['company_id', 'status']);
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('company_id')
                ->constrained('branches')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('company_id')
                ->constrained('branches')->nullOnDelete();
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('max_branches')->nullable()->after('max_assets');
        });

        Schema::create('branch_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('transfer_no', 40);
            $table->foreignId('from_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('to_branch_id')->constrained('branches')->cascadeOnDelete();
            // sent = dalam pengiriman, received = sudah diterima, cancelled = dibatalkan
            $table->string('status', 20)->default('sent');
            $table->text('notes')->nullable();
            $table->text('receive_notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'transfer_no']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('branch_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_transfer_id')->constrained('branch_transfers')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            // Salinan data aset saat dikirim (tetap terbaca walau aset dihapus).
            $table->string('asset_code', 50);
            $table->string('asset_name');
            $table->string('from_location')->nullable();
            $table->timestamps();
        });

        /*
         * Batas cabang bawaan per paket (bisa diubah di Backoffice > Paket).
         */
        $defaults = ['FREE' => 2, 'STARTER' => 2, 'PROFESSIONAL' => 3, 'ENTERPRISE' => null];

        foreach ($defaults as $code => $max) {
            DB::table('plans')->where('plan_code', $code)->update(['max_branches' => $max]);
        }

        // Paket lain (dibuat sendiri dari Backoffice) default 1 cabang.
        DB::table('plans')->whereNotIn('plan_code', array_keys($defaults))->update(['max_branches' => 1]);

        /*
         * Setiap perusahaan mendapat "Kantor Pusat"; semua aset lama masuk ke sana.
         */
        $now = now();

        foreach (DB::table('companies')->pluck('id') as $companyId) {
            $branchId = DB::table('branches')->insertGetId([
                'company_id' => $companyId,
                'branch_code' => 'PUSAT',
                'branch_name' => 'Kantor Pusat',
                'is_head_office' => true,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('assets')->where('company_id', $companyId)->update(['branch_id' => $branchId]);
        }

        foreach ($this->permissions as $p) {
            DB::table('permissions')->updateOrInsert(
                ['permission_code' => $p['permission_code']],
                $p + ['module' => 'branch', 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('permission_code', array_column($this->permissions, 'permission_code'))->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        Schema::dropIfExists('branch_transfer_items');
        Schema::dropIfExists('branch_transfers');

        Schema::table('plans', fn (Blueprint $table) => $table->dropColumn('max_branches'));

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::dropIfExists('branches');
    }
};
