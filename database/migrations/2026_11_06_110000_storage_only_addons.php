<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add-on hanya dijual untuk storage. Kuota user & aset naik lewat upgrade paket.
 * Add-on aset/user yang sudah terlanjur dibeli tetap berlaku sampai masa aktifnya habis.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('addon_rates')->whereIn('type', ['asset', 'user'])->update(['is_active' => 0, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('addon_rates')->whereIn('type', ['asset', 'user'])->update(['is_active' => 1, 'updated_at' => now()]);
    }
};
