<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * purchase_type:
     *   subscription -> beli / perpanjang / ganti paket (bisa masuk antrean)
     *   storage_addon -> tambah storage untuk paket yang sedang berjalan (prorata)
     */
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('purchase_type', 20)
                ->default('subscription')
                ->after('order_id');

            $table->unsignedBigInteger('storage_addon_bytes')
                ->default(0)
                ->after('storage_addon_gb');

            // Jumlah hari prorata untuk pembelian add-on.
            $table->unsignedInteger('prorated_days')
                ->nullable()
                ->after('storage_addon_price');

            $table->index(['company_id', 'purchase_type']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'purchase_type']);
            $table->dropColumn([
                'purchase_type',
                'storage_addon_bytes',
                'prorated_days',
            ]);
        });
    }
};
