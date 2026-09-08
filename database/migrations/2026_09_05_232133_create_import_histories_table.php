<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_histories', function (Blueprint $table) {

            $table->id();

            /*
             * Company pemilik data import.
             */
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            /*
             * User yang melakukan import.
             */
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
             * Modul yang diimport.
             * Contoh:
             * user
             * asset
             */
            $table->string('module', 50);

            /*
             * Nama file Excel.
             */
            $table->string('file_name');

            /*
             * Statistik import.
             */
            $table->unsignedInteger('total_rows')
                ->default(0);

            $table->unsignedInteger('success_rows')
                ->default(0);

            $table->unsignedInteger('failed_rows')
                ->default(0);

            /*
             * Status:
             * processing
             * completed
             * completed_with_errors
             * failed
             */
            $table->string('status', 30)
                ->default('processing');

            /*
             * Waktu proses.
             */
            $table->timestamp('started_at')
                ->nullable();

            $table->timestamp('finished_at')
                ->nullable();

            $table->timestamps();

            /*
             * Index untuk history per company.
             */
            $table->index([
                'company_id',
                'module',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_histories');
    }
};