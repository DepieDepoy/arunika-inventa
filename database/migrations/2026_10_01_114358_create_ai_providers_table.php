<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();

            // openai, gemini, anthropic, local, dll
            $table->string('provider_code', 50)->unique();

            // Nama yang ditampilkan di UI
            $table->string('provider_name', 100);

            // Contoh: https://api.openai.com/v1
            $table->string('api_base_url')->nullable();

            // Contoh: gpt-5, gemini-..., claude-...
            $table->string('model')->nullable();

            // Akan dienkripsi melalui model cast
            $table->text('api_key')->nullable();

            // Provider yang sedang digunakan
            $table->boolean('is_active')->default(false);

            // Jika nanti ada fallback
            $table->unsignedInteger('priority')->default(100);

            // Konfigurasi tambahan provider
            $table->json('settings')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};