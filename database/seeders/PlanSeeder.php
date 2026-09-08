<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'plan_code' => 'FREE',
                'plan_name' => 'Free Trial',
                'description' => 'Trial gratis untuk perusahaan baru.',
                'price' => 0,
                'duration_days' => 30,
                'max_users' => 5,
                'max_assets' => 100,
                'status' => 1,
            ],

            [
                'plan_code' => 'STARTER',
                'plan_name' => 'Starter',
                'description' => 'Paket untuk perusahaan kecil.',
                'price' => 99000,
                'duration_days' => 30,
                'max_users' => 10,
                'max_assets' => 500,
                'status' => 1,
            ],

            [
                'plan_code' => 'PROFESSIONAL',
                'plan_name' => 'Professional',
                'description' => 'Paket untuk perusahaan berkembang.',
                'price' => 299000,
                'duration_days' => 30,
                'max_users' => 50,
                'max_assets' => 5000,
                'status' => 1,
            ],

            [
                'plan_code' => 'ENTERPRISE',
                'plan_name' => 'Enterprise',
                'description' => 'Paket untuk perusahaan dengan kebutuhan skala besar.',
                'price' => 999000,
                'duration_days' => 30,
                'max_users' => null,
                'max_assets' => null,
                'status' => 1,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                [
                    'plan_code' => $plan['plan_code'],
                ],
                $plan
            );
        }
    }
}
