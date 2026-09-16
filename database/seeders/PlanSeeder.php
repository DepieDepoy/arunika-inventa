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

                'price_monthly' => 0,
                'price_yearly' => 0,
                'trial_days' => 14,

                'max_users' => 5,
                'max_assets' => 5,

                'status' => 1,
            ],

            [
                'plan_code' => 'STARTER',
                'plan_name' => 'Starter',
                'description' => 'Paket untuk perusahaan kecil.',

                'price_monthly' => 99000,
                'price_yearly' => 990000,
                'trial_days' => 0,

                'max_users' => 10,
                'max_assets' => 50,

                'status' => 1,
            ],

            [
                'plan_code' => 'PROFESSIONAL',
                'plan_name' => 'Professional',
                'description' => 'Paket untuk perusahaan berkembang.',

                'price_monthly' => 299000,
                'price_yearly' => 2990000,
                'trial_days' => 0,

                'max_users' => 50,
                'max_assets' => 200,

                'status' => 1,
            ],

            [
                'plan_code' => 'ENTERPRISE',
                'plan_name' => 'Enterprise',
                'description' => 'Paket untuk perusahaan dengan kebutuhan skala besar.',

                'price_monthly' => 999000,
                'price_yearly' => 9990000,
                'trial_days' => 0,

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