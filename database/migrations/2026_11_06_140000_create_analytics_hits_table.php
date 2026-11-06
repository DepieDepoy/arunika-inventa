<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kunjungan & event funnel (page_view, signup, login) dari halaman publik.
     */
    public function up(): void
    {
        Schema::create('analytics_hits', function (Blueprint $table) {
            $table->id();
            $table->string('event', 20);             // page_view | signup | login
            $table->string('page', 30)->nullable();  // landing | register | login
            $table->string('path', 191)->nullable();
            $table->string('locale', 5)->nullable();
            $table->string('visitor_id', 40);
            $table->string('ip', 45)->nullable();    // dikosongkan setelah masa simpan IP
            $table->string('device', 10)->default('desktop');
            $table->string('os', 30)->nullable();
            $table->string('browser', 40)->nullable();
            $table->string('source', 60)->default('Langsung');
            $table->string('referrer_host', 191)->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event', 'created_at']);
            $table->index('visitor_id');
            $table->index('company_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_hits');
    }
};
