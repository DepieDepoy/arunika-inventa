<?php

namespace Database\Seeders;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('company_code', 'pt_arunika_solusi_inovasi')->first();

        $role = Role::where('role_code', 'super_admin')->first();

        User::firstOrCreate(
            ['email' => 'dasril@arunikasolusiinovasi.co.id'],
            [
                'company_id' => $company->id,
                'role_id' => $role->id,
                'name' => 'Dasril',
                'phone' => '087888768910',
                'password' => Hash::make('admin12345'),
                'status' => 1,
            ]
        );
    }
}