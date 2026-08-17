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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete(); //tambahan
            $table->string('name');
            $table->string('phone',20)->unique()->comment('Nomor WhatsApp'); //tambahan
            $table->string('email')->unique();
            $table->string('photo', 255)->nullable();//tambahan
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->tinyInteger('status')->default(1)->comment('1=Aktif, 0=Nonaktif'); //tambahan
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip',45)->nullable();
            $table->string('last_login_device',255)->nullable();
            $table->rememberToken();
            $table->timestamps();//otomatis terbentuk 2 kolom create dan update
            $table->softDeletes();//tambahan
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
