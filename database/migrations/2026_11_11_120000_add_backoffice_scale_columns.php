<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backoffice server side: storage terpakai per perusahaan disimpan di kolom
 * (diisi perintah backoffice:scan-storage) agar bisa diurutkan & dijumlah di SQL,
 * plus indeks untuk urutan / agregat yang sering dipakai.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'storage_used_bytes')) {
                $table->unsignedBigInteger('storage_used_bytes')->default(0)->index();
            }

            if (!Schema::hasColumn('companies', 'storage_scanned_at')) {
                $table->timestamp('storage_scanned_at')->nullable();
            }

            $table->index('created_at', 'companies_created_at_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['last_login_at', 'company_id'], 'users_last_login_company_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_last_login_company_index');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex('companies_created_at_index');
            $table->dropColumn(['storage_used_bytes', 'storage_scanned_at']);
        });
    }
};
