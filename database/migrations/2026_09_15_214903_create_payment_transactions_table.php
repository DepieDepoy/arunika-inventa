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
            | TRANSACTION
            |--------------------------------------------------------------------------
            */
            $table->string('order_id')->unique();

            $table->string('midtrans_transaction_id')
                ->nullable()
                ->index();

            $table->string('payment_type')
                ->nullable();

            $table->string('transaction_status')
                ->default('pending')
                ->index();

            $table->string('fraud_status')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | PAYMENT DETAIL
            |--------------------------------------------------------------------------
            */
            $table->decimal('gross_amount', 15, 2);

            $table->string('va_number')
                ->nullable();

            $table->string('bank')
                ->nullable();

            $table->text('qr_string')
                ->nullable();

            $table->string('payment_url')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | TIMESTAMP PAYMENT
            |--------------------------------------------------------------------------
            */
            $table->timestamp('transaction_time')
                ->nullable();

            $table->timestamp('settlement_time')
                ->nullable();

            $table->timestamp('expiry_time')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | RAW RESPONSE
            |--------------------------------------------------------------------------
            */
            $table->json('midtrans_response')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};