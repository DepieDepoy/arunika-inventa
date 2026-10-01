<?php

namespace App\Services\AI;

interface AiProviderInterface
{
    /**
     * Memproses prompt menjadi struktur data
     * yang dapat dipahami oleh Vasetra AI Engine.
     */
    public function understand(
        string $prompt,
        array $context = []
    ): array;

    /**
     * Nama provider.
     */
    public function providerName(): string;

    /**
     * Model yang sedang digunakan.
     */
    public function model(): ?string;
}