<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Landing page dua bahasa (id / en).
 *
 * - site_contents.locale : konten CMS per bahasa (gambar tetap dibagi, disimpan di baris "id").
 * - plans.description_en : deskripsi paket untuk landing berbahasa Inggris.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_contents', function (Blueprint $table) {
            $table->string('locale', 5)->default('id')->after('key');
            $table->dropUnique(['key']);
            $table->unique(['key', 'locale']);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->string('description_en')->nullable()->after('description');
        });

        $english = [
            'FREE' => 'Free trial for new companies.',
            'STARTER' => 'For small companies.',
            'PROFESSIONAL' => 'For growing companies.',
            'ENTERPRISE' => 'For companies with large-scale needs.',
        ];

        foreach ($english as $code => $text) {
            DB::table('plans')->where('plan_code', $code)->whereNull('description_en')->update(['description_en' => $text]);
        }
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('description_en');
        });

        Schema::table('site_contents', function (Blueprint $table) {
            $table->dropUnique(['key', 'locale']);
            $table->unique(['key']);
            $table->dropColumn('locale');
        });
    }
};
