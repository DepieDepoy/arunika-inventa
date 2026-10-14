<?php

namespace Database\Seeders;

use App\Models\AiProvider;
use Illuminate\Database\Seeder;

class AiProviderSeeder extends Seeder
{
    public function run(): void
    {
        AiProvider::updateOrCreate(
            [
                'provider_code' => 'openai',
            ],
            [
                'provider_name' => 'OpenAI',
                'api_base_url' => 'https://api.openai.com/v1',

                /*
                 * Jangan isi API key di sini.
                 */
                'model' => null,
                'api_key' => null,

                'is_active' => false,
                'priority' => 10,

                'settings' => [
                    'temperature' => 0.1,
                ],
            ]
        );

        AiProvider::updateOrCreate(
            [
                'provider_code' => 'gemini',
            ],
            [
                'provider_name' => 'Google Gemini',
                'api_base_url' => null,
                'model' => null,
                'api_key' => null,
                'is_active' => false,
                'priority' => 20,
                'settings' => [],
            ]
        );

        AiProvider::updateOrCreate(
            [
                'provider_code' => 'anthropic',
            ],
            [
                'provider_name' => 'Anthropic',
                'api_base_url' => null,
                'model' => null,
                'api_key' => null,
                'is_active' => false,
                'priority' => 30,
                'settings' => [],
            ]
        );
    }
}