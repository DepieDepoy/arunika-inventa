<?php

namespace Database\Seeders;

use App\Models\StorageAddonRate;
use Illuminate\Database\Seeder;

class StorageAddonRateSeeder extends Seeder
{
    public function run(): void
    {
        StorageAddonRate::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Storage Tambahan',
                'unit_bytes' => 536870912, // 512 MiB per unit
                'price_monthly' => 10000,
                'price_yearly' => 120000,
                'max_units' => 40, // Maksimal 20 GiB
                'is_active' => true,
            ]
        );
    }
}
