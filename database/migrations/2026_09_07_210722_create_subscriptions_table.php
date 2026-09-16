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

            // =====================================================
            // COMPANY & PLAN
            // =====================================================
            $table->foreignId('company_id')
                ->constrained('companies')
                ->restrictOnDelete();

            $table->foreignId('plan_id')
                ->constrained('plans')
                ->restrictOnDelete();

            // =====================================================
            // BILLING
            // monthly / yearly / trial
            // =====================================================
            $table->string('billing_cycle', 20)
                ->default('monthly');

            // Harga yang digunakan saat subscription dibuat.
            // Disimpan sebagai snapshot agar histori tidak berubah
            // jika harga pada tabel plans berubah di kemudian hari.
            $table->decimal('price', 15, 2)
                ->default(0);

            // =====================================================
            // SUBSCRIPTION PERIOD
            // =====================================================
            $table->date('start_date');
            $table->date('end_date');

            // =====================================================
            // SUBSCRIPTION STATUS
            // active   = sedang digunakan
            // expired  = sudah berakhir
            // replaced = digantikan subscription baru
            // cancelled = dibatalkan
            // =====================================================
            $table->string('status', 20)
                ->default('active');

            // =====================================================
            // PAYMENT STATUS
            // pending / paid / failed
            // =====================================================
            $table->string('payment_status', 20)
                ->default('paid');

            $table->timestamps();

            // =====================================================
            // INDEX
            // =====================================================
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