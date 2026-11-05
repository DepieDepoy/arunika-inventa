<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit trail per company (PT).
     *
     * Data bersifat append-only: tidak ada updated_at,
     * dan model AuditLog menolak update/delete.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            /*
             * Pelaku. Nullable untuk aksi sistem / tamu.
             * Nama disimpan sebagai snapshot agar tetap terbaca
             * walaupun user sudah dihapus.
             */
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name', 150)->nullable();

            /*
             * Contoh event: created, updated, deleted, restored,
             * login, logout, login_failed, login_locked, registered,
             * password_reset, email_verified, exported,
             * import_started, import_finished, permission_updated.
             */
            $table->string('event', 50);
            $table->string('module', 50);
            $table->string('description', 255);

            // Data yang terdampak (polymorphic, tanpa FK).
            $table->string('auditable_type', 150)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Konteks request.
            $table->string('source', 10)->default('web'); // web | api | system
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('url', 500)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'created_at']);
            $table->index(['company_id', 'module']);
            $table->index(['company_id', 'event']);
            $table->index(['company_id', 'user_id']);
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
