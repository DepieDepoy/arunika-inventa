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

            // =====================================================
            // PLAN INFORMATION
            // =====================================================
            $table->string('plan_code', 50)->unique();
            $table->string('plan_name', 100);
            $table->text('description')->nullable();

            // =====================================================
            // PRICING
            // =====================================================
            $table->decimal('price_monthly', 15, 2)->default(0);
            $table->decimal('price_yearly', 15, 2)->default(0);

            // =====================================================
            // FREE TRIAL
            // =====================================================
            $table->unsignedInteger('trial_days')->default(0);

            // =====================================================
            // PLAN LIMITS
            // NULL = UNLIMITED
            // =====================================================
            $table->unsignedInteger('max_users')->nullable();
            $table->unsignedInteger('max_assets')->nullable();

            // =====================================================
            // STATUS
            // 1 = Active
            // 0 = Inactive
            // =====================================================
            $table->tinyInteger('status')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};