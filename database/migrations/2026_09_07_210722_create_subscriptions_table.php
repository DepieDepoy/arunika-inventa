<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            // COMPANY & PLAN
            $table->foreignId('company_id')
                ->constrained('companies')
                ->restrictOnDelete();

            $table->foreignId('plan_id')
                ->constrained('plans')
                ->restrictOnDelete();

            // BILLING
            $table->string('billing_cycle', 20)->default('monthly');

            // Snapshot harga paket
            $table->decimal('price', 15, 2)->default(0);

            // STORAGE ADD-ON
            $table->decimal('storage_addon_gb', 8, 2)->default(0);
            $table->decimal('storage_addon_price', 15, 2)->default(0);

            // STORAGE CAPACITY SNAPSHOT (BYTE)
            // Kapasitas dasar paket saat subscription dibuat
            $table->unsignedBigInteger('base_storage_bytes')
                ->default(536870912); // 500 MiB

            // Kapasitas tambahan yang dibeli
            $table->unsignedBigInteger('storage_addon_bytes')
                ->default(0);

            // Total kuota = base_storage_bytes + storage_addon_bytes
            $table->unsignedBigInteger('total_storage_bytes')
                ->default(536870912); // 500 MiB

            // DISCOUNT & TOTAL PRICE
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);

            // SUBSCRIPTION PERIOD
            $table->date('start_date');
            $table->date('end_date');

            // SUBSCRIPTION STATUS
            $table->string('status', 20)->default('active');

            // PAYMENT STATUS
            $table->string('payment_status', 20)->default('paid');

            $table->timestamps();

            // INDEX
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'billing_cycle']);
            $table->index('end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
