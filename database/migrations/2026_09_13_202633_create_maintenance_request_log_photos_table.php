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
        Schema::create('maintenance_request_log_photos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('maintenance_request_log_id')
                ->constrained(
                    'maintenance_request_logs',
                    'id',
                    'log_photos_log_id_fk'
                )
                ->cascadeOnDelete();

            $table->string('file_path');

            $table->string('caption')->nullable();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained(
                    'users',
                    'id',
                    'log_photos_uploaded_by_fk'
                )
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_request_log_photos');
    }
};