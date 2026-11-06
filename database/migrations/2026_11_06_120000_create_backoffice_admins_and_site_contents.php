<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backoffice Vasetra (pegawai Vasetra sebagai penjual platform).
 *
 * Sengaja TERPISAH dari tabel `users` milik pelanggan:
 * - backoffice_admins     : akun pegawai Vasetra (login & guard sendiri).
 * - backoffice_audit_logs : jejak aktivitas di backoffice.
 * - site_contents       : konten landing page per bagian (JSON).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backoffice_admins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email')->unique();
            $table->string('password');
            // super_admin: semua akses (konten, harga, pegawai, log) · editor: konten landing saja.
            $table->string('role', 20)->default('editor');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('backoffice_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backoffice_admin_id')->nullable()->constrained('backoffice_admins')->nullOnDelete();
            $table->string('admin_name', 150)->nullable();
            $table->string('event', 50);
            $table->string('description', 255);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->json('value');
            $table->foreignId('updated_by')->nullable()->constrained('backoffice_admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_contents');
        Schema::dropIfExists('backoffice_audit_logs');
        Schema::dropIfExists('backoffice_admins');
    }
};
