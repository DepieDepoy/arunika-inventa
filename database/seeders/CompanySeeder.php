<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::firstOrCreate(
            ['company_code' => 'pt_arunika_solusi_inovasi'],
            [
                'company_name' => 'PT Arunika Solusi Inovasi',
                'status' => 1,
            ]
        );
    }
}