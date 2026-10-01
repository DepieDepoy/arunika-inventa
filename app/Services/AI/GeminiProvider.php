<?php

namespace App\Services\AI;

use App\Models\AiProvider;
use RuntimeException;

class GeminiProvider implements AiProviderInterface
{
    protected AiProvider $config;

    public function __construct()
    {
        $this->config = AiProvider::query()
            ->where('provider_code', 'gemini')
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function understand(
        string $prompt,
        array $context = []
    ): array {
        throw new RuntimeException(
            'Gemini provider belum dikonfigurasi untuk digunakan.'
        );
    }

    public function providerName(): string
    {
        return $this->config->provider_name;
    }

    public function model(): ?string
    {
        return $this->config->model;
    }
}