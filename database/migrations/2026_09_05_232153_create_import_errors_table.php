<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_errors', function (Blueprint $table) {

            $table->id();

            /*
             * History import.
             */
            $table->foreignId('import_history_id')
                ->constrained('import_histories')
                ->cascadeOnDelete();

            /*
             * Nomor baris Excel.
             */
            $table->unsignedInteger('row_number');

            /*
             * Data asli dari Excel.
             *
             * Disimpan sebagai JSON supaya fleksibel
             * untuk User maupun Asset.
             */
            $table->json('data')
                ->nullable();

            /*
             * Pesan error.
             *
             * Contoh:
             * NIK sudah terdaftar.
             * Email sudah terdaftar.
             */
            $table->text('error_message');

            $table->timestamps();

            /*
             * Index.
             */
            $table->index([
                'import_history_id',
                'row_number',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_errors');
    }
};