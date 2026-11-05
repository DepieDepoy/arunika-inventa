<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 1. subscriptions.kind / priority / parent_subscription_id
     *    regular -> paket biasa
     *    upgrade -> upgrade sementara/permanen yang "menimpa" paket regular
     *               (priority lebih tinggi), paket regular tetap berjalan.
     *
     * 2. addon_rates
     *    Tarif add-on per komponen (storage / asset / user), diatur dari DB.
     *
     * 3. subscription_addons
     *    Add-on yang dibeli di tengah periode, masing-masing punya masa berlaku.
     *
     * 4. payment_transactions.duration_months
     *    Durasi upgrade / add-on (NULL = sampai akhir paket).
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('kind', 20)->default('regular')->after('plan_id');
            $table->unsignedTinyInteger('priority')->default(0)->after('kind');
            $table->unsignedBigInteger('parent_subscription_id')->nullable()->after('priority');

            $table->index(['company_id', 'kind', 'status']);
            $table->index('parent_subscription_id');
        });

        Schema::create('addon_rates', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->unique(); // storage | asset | user
            $table->string('name', 100);
            $table->unsignedBigInteger('unit_value');   // bytes / jumlah asset / jumlah user per unit
            $table->string('unit_label', 50);           // contoh: "500 MB", "10 Asset"
            $table->unsignedBigInteger('price_monthly'); // per unit per bulan
            $table->unsignedBigInteger('price_yearly');  // per unit per tahun
            $table->unsignedInteger('max_units')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        /*
         * Tarif awal. Storage mengikuti tarif yang sudah berjalan
         * (storage_addon_rates) agar harga pelanggan tidak berubah.
         * Semua nilai dapat diubah langsung di tabel addon_rates.
         */
        $storage = DB::table('storage_addon_rates')->where('is_active', true)->first();

        DB::table('addon_rates')->insert([
            [
                'type' => 'storage',
                'name' => 'Tambahan Storage',
                'unit_value' => (int) ($storage->unit_bytes ?? 536870912),
                'unit_label' => round(((int) ($storage->unit_bytes ?? 536870912)) / 1048576) . ' MB',
                'price_monthly' => (int) ($storage->price_monthly ?? 10000),
                'price_yearly' => (int) ($storage->price_yearly ?? 120000),
                'max_units' => (int) ($storage->max_units ?? 40),
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'asset',
                'name' => 'Tambahan Asset',
                'unit_value' => 10,
                'unit_label' => '10 Asset',
                'price_monthly' => 20000,
                'price_yearly' => 240000,
                'max_units' => 50,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'user',
                'name' => 'Tambahan User',
                'unit_value' => 5,
                'unit_label' => '5 User',
                'price_monthly' => 25000,
                'price_yearly' => 300000,
                'max_units' => 20,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Schema::create('subscription_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            // Paket regular tempat add-on menempel.
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->unsignedBigInteger('payment_transaction_id')->nullable();

            $table->string('type', 20); // storage | asset | user
            $table->unsignedInteger('units');
            $table->unsignedBigInteger('unit_value');
            $table->unsignedBigInteger('total_value'); // units x unit_value
            $table->decimal('price', 15, 2)->default(0);
            $table->unsignedTinyInteger('duration_months')->nullable(); // NULL = sampai akhir paket

            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('pending'); // pending | active | cancelled

            $table->timestamps();

            $table->index(['company_id', 'status', 'start_date', 'end_date'], 'sub_addons_company_active_idx');
            $table->index('payment_transaction_id');
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->unsignedTinyInteger('duration_months')->nullable()->after('prorated_days');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn('duration_months');
        });

        Schema::dropIfExists('subscription_addons');
        Schema::dropIfExists('addon_rates');

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'kind', 'status']);
            $table->dropIndex(['parent_subscription_id']);
            $table->dropColumn(['kind', 'priority', 'parent_subscription_id']);
        });
    }
};
