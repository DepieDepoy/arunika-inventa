<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_addon_rates', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100)
                ->default('Storage Tambahan');

            // 512 MiB per unit; 40 unit = 20 GiB.
            $table->unsignedBigInteger('unit_bytes')
                ->default(536870912);

            // Harga per unit dalam Rupiah.
            $table->unsignedBigInteger('price_monthly')
                ->default(10000);

            $table->unsignedBigInteger('price_yearly')
                ->default(120000);

            $table->unsignedInteger('max_units')
                ->default(40);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_addon_rates');
    }
};