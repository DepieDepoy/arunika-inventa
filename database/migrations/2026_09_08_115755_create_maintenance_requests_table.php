<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {

            $table->id();

            // Company pemilik data
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            // Asset yang diminta untuk diperbaiki / dirawat
            $table->foreignId('asset_id')
                ->constrained('assets')
                ->cascadeOnDelete();

            // User yang membuat request
            $table->foreignId('requested_by')
                ->constrained('users')
                ->restrictOnDelete();

            // User dari tim maintenance yang menangani request
            // Diisi otomatis berdasarkan user yang melakukan pekerjaan
            $table->foreignId('handled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Jenis request
            // maintenance / repair / problem / other
            $table->string('request_type', 30);

            // Penjelasan dari user
            $table->text('description');

            // Status request
            // pending / approved / in_progress / completed / rejected / cancelled
            $table->string('status', 30)
                ->default('pending');

            // Waktu request mulai ditangani
            $table->timestamp('handled_at')
                ->nullable();

            // Waktu request selesai
            $table->timestamp('completed_at')
                ->nullable();

            $table->timestamps();

            // Index untuk pencarian/filter
            $table->index(['company_id', 'status']);
            $table->index(['asset_id', 'status']);
            $table->index('requested_by');
            $table->index('handled_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};