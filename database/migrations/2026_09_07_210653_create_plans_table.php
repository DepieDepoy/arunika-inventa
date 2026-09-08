<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            $table->string('plan_code', 50)->unique();
            $table->string('plan_name', 100);

            $table->text('description')->nullable();

            $table->decimal('price', 15, 2)->default(0);

            $table->unsignedInteger('duration_days')->default(30);

            $table->unsignedInteger('max_users')->nullable();
            $table->unsignedInteger('max_assets')->nullable();

            $table->tinyInteger('status')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};