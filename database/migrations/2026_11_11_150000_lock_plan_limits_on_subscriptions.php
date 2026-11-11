<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kuota user / aset / cabang disimpan per langganan (seperti harga & storage).
 * Mengubah paket di backoffice tidak lagi mengubah kuota pelanggan yang sedang
 * berlangganan; nilai baru berlaku untuk pembelian / perpanjangan berikutnya.
 *
 * Langganan yang sudah ada dikunci dengan kuota paket SAAT INI agar tidak ada
 * pelanggan yang kuotanya berubah ketika migrasi ini dijalankan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'max_users')) {
                $table->unsignedInteger('max_users')->nullable()->after('total_storage_bytes');
            }

            if (!Schema::hasColumn('subscriptions', 'max_assets')) {
                $table->unsignedInteger('max_assets')->nullable()->after('max_users');
            }

            if (!Schema::hasColumn('subscriptions', 'max_branches')) {
                $table->unsignedInteger('max_branches')->nullable()->after('max_assets');
            }

            if (!Schema::hasColumn('subscriptions', 'limits_locked')) {
                $table->boolean('limits_locked')->default(false)->after('max_branches');
            }
        });

        DB::table('subscriptions')
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('subscriptions.limits_locked', false)
            ->update([
                'subscriptions.max_users' => DB::raw('plans.max_users'),
                'subscriptions.max_assets' => DB::raw('plans.max_assets'),
                'subscriptions.max_branches' => DB::raw('plans.max_branches'),
                'subscriptions.limits_locked' => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['max_users', 'max_assets', 'max_branches', 'limits_locked']);
        });
    }
};
