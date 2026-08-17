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
        Schema::create('roles', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('company_id');

            $table->string('role_name', 100);

            $table->string('role_code', 50);

            $table->tinyInteger('status')
                  ->default(1)
                  ->comment('1=Aktif, 0=Nonaktif');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign Key
            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();

            // Satu perusahaan tidak boleh memiliki role_code yang sama
            $table->unique(['company_id', 'role_code']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};