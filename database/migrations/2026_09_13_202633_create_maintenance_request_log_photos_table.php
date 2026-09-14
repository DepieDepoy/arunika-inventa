<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_request_log_photos', function (Blueprint $table) {

            $table->id();

            $table->foreignId('maintenance_request_log_id')
                ->constrained('maintenance_request_logs')
                ->cascadeOnDelete();

            $table->string('file_path');

            $table->string('caption', 255)
                ->nullable();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_request_log_photos');
    }
};