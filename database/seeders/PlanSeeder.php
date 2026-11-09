<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $MB = 1024 * 1024;
        $GB = 1024 * 1024 * 1024;

        $plans = [
            [
                'plan_code' => 'FREE',
                'plan_name' => 'Free Trial',
                'description' => 'Trial gratis untuk perusahaan baru.',
                'description_en' => 'Free trial for new companies.',

                'price_monthly' => 0,
                'price_yearly' => 0,
                'trial_days' => 7,

                'max_users' => 5,
                'max_assets' => 5,
                'max_branches' => 2,
                'max_storage_bytes' => 500 * $MB,

                'status' => 1,
            ],

            [
                'plan_code' => 'STARTER',
                'plan_name' => 'Starter',
                'description' => 'Paket untuk perusahaan kecil.',
                'description_en' => 'For small companies.',

                'price_monthly' => 99000,
                'price_yearly' => 990000,
                'trial_days' => 0,

                'max_users' => 10,
                'max_assets' => 50,
                'max_branches' => 2,
                'max_storage_bytes' => 1 * $GB,

                'status' => 1,
            ],

            [
                'plan_code' => 'PROFESSIONAL',
                'plan_name' => 'Professional',
                'description' => 'Paket untuk perusahaan berkembang.',
                'description_en' => 'For growing companies.',

                'price_monthly' => 299000,
                'price_yearly' => 2990000,
                'trial_days' => 0,

                'max_users' => 50,
                'max_assets' => 200,
                'max_branches' => 3,
                'max_storage_bytes' => 5 * $GB,

                'status' => 1,
            ],

            [
                'plan_code' => 'ENTERPRISE',
                'plan_name' => 'Enterprise',
                'description' => 'Paket untuk perusahaan dengan kebutuhan skala besar.',
                'description_en' => 'For companies with large-scale needs.',

                'price_monthly' => 999000,
                'price_yearly' => 9990000,
                'trial_days' => 0,

                'max_users' => null,
                'max_assets' => null,
                'max_branches' => null,
                'max_storage_bytes' => 20 * $GB,

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
