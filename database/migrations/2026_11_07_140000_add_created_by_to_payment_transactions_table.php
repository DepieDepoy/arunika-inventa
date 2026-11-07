<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mencatat user yang membuat transaksi (untuk halaman Transaksi di backoffice).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('plan_id')->constrained('users')->nullOnDelete();
            $table->index(['transaction_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropIndex(['transaction_status', 'created_at']);
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
