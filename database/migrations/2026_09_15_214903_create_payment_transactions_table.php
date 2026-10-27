
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | RELATION
            |--------------------------------------------------------------------------
            */
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('subscription_id')
                ->nullable()
                ->constrained('subscriptions')
                ->nullOnDelete();

            $table->foreignId('plan_id')
                ->constrained('plans')
                ->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | TRANSACTION IDENTITY
            |--------------------------------------------------------------------------
            */
            $table->string('order_id')->unique();

            // Snapshot nama paket saat transaksi dibuat
            $table->string('plan_name');

            $table->string('billing_cycle', 20)
                ->default('monthly');

            /*
            |--------------------------------------------------------------------------
            | PRICE BREAKDOWN
            |--------------------------------------------------------------------------
            */
            // Harga paket saja
            $table->decimal('package_price', 15, 2)->default(0);

            // Jumlah storage tambahan dalam GB
            $table->decimal('storage_addon_gb', 8, 2)->default(0);

            // Harga storage tambahan
            $table->decimal('storage_addon_price', 15, 2)->default(0);

            // Diskon transaksi
            $table->decimal('discount_amount', 15, 2)->default(0);

            // Total akhir yang ditagihkan ke pelanggan
            $table->decimal('gross_amount', 15, 2);

            /*
            |--------------------------------------------------------------------------
            | PAYMENT STATUS
            |--------------------------------------------------------------------------
            */
            $table->string('transaction_status', 30)
                ->default('pending')
                ->index();

            $table->string('payment_type')->nullable();

            $table->string('fraud_status')->nullable();

            // ID transaksi dari payment gateway, jika tersedia
            $table->string('midtrans_transaction_id')
                ->nullable()
                ->index();

            /*
            |--------------------------------------------------------------------------
            | PAYMENT DETAILS
            |--------------------------------------------------------------------------
            */
            $table->string('va_number')->nullable();
            $table->string('bank')->nullable();
            $table->text('qr_string')->nullable();
            $table->string('payment_url')->nullable();

            /*
            |--------------------------------------------------------------------------
            | PAYMENT TIMESTAMPS
            |--------------------------------------------------------------------------
            */
            $table->timestamp('transaction_time')->nullable();
            $table->timestamp('settlement_time')->nullable();
            $table->timestamp('expiry_time')->nullable();

            /*
            |--------------------------------------------------------------------------
            | GATEWAY RESPONSE
            |--------------------------------------------------------------------------
            |
            | Tetap menyimpan respons asli gateway sebagai JSON.
            | Rincian invoice sendiri disimpan di kolom-kolom satuan.
            |
            */
            $table->json('midtrans_response')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | INDEX
            |--------------------------------------------------------------------------
            */
            $table->index(['company_id', 'transaction_status']);
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
