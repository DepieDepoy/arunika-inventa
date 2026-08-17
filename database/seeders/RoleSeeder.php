<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'role_name' => 'Super Admin',
                'role_code' => 'super_admin',
            ],
            [
                'role_name' => 'Owner',
                'role_code' => 'owner',
            ],
            [
                'role_name' => 'Admin',
                'role_code' => 'admin',
            ],
            [
                'role_name' => 'HR',
                'role_code' => 'hr',
            ],
            [
                'role_name' => 'Manager',
                'role_code' => 'manager',
            ],
            [
                'role_name' => 'Staff',
                'role_code' => 'staff',
            ],
        ];

        foreach ($roles as $role) {

            Role::firstOrCreate(
                ['role_code' => $role['role_code']],
                [
                    'role_name' => $role['role_name'],
                    'status'    => 1,
                ]
            );

        }
    }
}