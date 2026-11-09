<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penyempurnaan multi-cabang:
 *  - Setiap user wajib punya cabang; user lama (tanpa cabang) masuk Kantor Pusat.
 *  - branches.service_branch_id: cabang yang teknisinya menangani maintenance
 *    cabang ini (NULL = otomatis: teknisi cabang sendiri, bila tidak ada -> Kantor Pusat).
 *
 * Cakupan data ditentukan cabang user: Administrator Kantor Pusat melihat semua
 * cabang; user lain (termasuk Administrator cabang) hanya cabangnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('service_branch_id')->nullable()->after('is_head_office')
                ->constrained('branches')->nullOnDelete();
        });

        $headOffices = DB::table('branches')->where('is_head_office', true)->whereNull('deleted_at')->pluck('id', 'company_id');

        foreach ($headOffices as $companyId => $branchId) {
            DB::table('users')->where('company_id', $companyId)->whereNull('branch_id')->update(['branch_id' => $branchId]);
            DB::table('assets')->where('company_id', $companyId)->whereNull('branch_id')->update(['branch_id' => $branchId]);
        }
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_branch_id');
        });
    }
};
