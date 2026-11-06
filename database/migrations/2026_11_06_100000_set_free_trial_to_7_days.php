<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Masa coba gratis menjadi 7 hari untuk pendaftar baru.
 * Trial yang sedang berjalan tidak diubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')->where('plan_code', 'FREE')->update(['trial_days' => 7, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('plans')->where('plan_code', 'FREE')->update(['trial_days' => 14, 'updated_at' => now()]);
    }
};
