<?php

namespace App\Services\AI;

use App\Models\AiProvider;
use RuntimeException;

class AiProviderManager
{
    public function active(): AiProvider
    {
        $provider = AiProvider::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->first();

        if (!$provider) {
            throw new RuntimeException(
                'Belum ada AI provider yang aktif.'
            );
        }

        return $provider;
    }

    public function driver(
        ?AiProvider $provider = null
    ): AiProviderInterface {
        $provider ??= $this->active();

        return match ($provider->provider_code) {
            'openai' => app(OpenAiProvider::class),

            'gemini' => app(GeminiProvider::class),

            'anthropic' => app(AnthropicProvider::class),

            default => throw new RuntimeException(
                "AI provider [{$provider->provider_code}] belum didukung."
            ),
        };
    }

    public function activeDriver(): AiProviderInterface
    {
        return $this->driver(
            $this->active()
        );
    }
}