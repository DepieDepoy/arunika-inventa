<?php

namespace App\Services\Intelligence;

use App\Services\AI\AiProviderManager;
use Illuminate\Support\Facades\Log;
use Throwable;

class VasetraAiUnderstandingService
{
    public function __construct(
        protected AiProviderManager $providerManager
    ) {
    }


    /**
     * Understand natural language using the configured AI provider.
     */
    public function understand(
        string $message,
        array $context = []
    ): array {
        $message =
            trim($message);

        if ($message === '') {
            return $this->unknown();
        }

        try {

            $provider =
                $this->providerManager
                    ->provider();

            $result =
                $provider->understand(
                    $message,
                    $context
                );

            return
                $this->normalize(
                    $result
                );

        } catch (Throwable $e) {

            Log::warning(
                'Tera AI understanding failed.',
                [
                    'message' =>
                        $message,

                    'error' =>
                        $e->getMessage(),
                ]
            );

            return $this->fallback(
                $message
            );
        }
    }


    /**
     * Normalize AI result.
     */
    protected function normalize(
        array $result
    ): array {
        $entities =
            $result['entities'] ?? [];

        return [
            'intent' =>
                $result['intent']
                ?? 'unknown',

            'confidence' =>
                (float) (
                    $result['confidence']
                    ?? 0
                ),

            'entities' => [
                'asset_name' =>
                    $this->stringOrNull(
                        $entities['asset_name']
                        ?? null
                    ),

                'asset_code' =>
                    $this->stringOrNull(
                        $entities['asset_code']
                        ?? null
                    ),

                'category' =>
                    $this->stringOrNull(
                        $entities['category']
                        ?? null
                    ),

                'subcategory' =>
                    $this->stringOrNull(
                        $entities['subcategory']
                        ?? null
                    ),

                'vendor' =>
                    $this->stringOrNull(
                        $entities['vendor']
                        ?? null
                    ),

                'responsible_user' =>
                    $this->stringOrNull(
                        $entities['responsible_user']
                        ?? null
                    ),

                'purchase_price' =>
                    $this->numberOrNull(
                        $entities['purchase_price']
                        ?? null
                    ),

                'condition' =>
                    $this->stringOrNull(
                        $entities['condition']
                        ?? null
                    ),

                'quantity' =>
                    $this->integerOrNull(
                        $entities['quantity']
                        ?? null
                    ),

                'date' =>
                    $this->stringOrNull(
                        $entities['date']
                        ?? null
                    ),

                'status' =>
                    $this->stringOrNull(
                        $entities['status']
                        ?? null
                    ),

                'search' =>
                    $this->stringOrNull(
                        $entities['search']
                        ?? null
                    ),
            ],

            'missing_fields' =>
                array_values(
                    array_filter(
                        $result['missing_fields']
                        ?? [],
                        fn ($value) =>
                            is_string($value)
                    )
                ),

            'reply' =>
                (string) (
                    $result['reply']
                    ?? ''
                ),
        ];
    }


    /**
     * Fallback if provider is unavailable.
     *
     * This keeps Tera functional while AI provider
     * is being configured.
     */
    protected function fallback(
        string $message
    ): array {
        $normalized =
            strtolower(
                trim($message)
            );

        /*
        |--------------------------------------------------------------------------
        | Asset count
        |--------------------------------------------------------------------------
        */

        if (
            $this->containsAny(
                $normalized,
                [
                    'berapa asset',
                    'berapa aset',
                    'brp asset',
                    'brp aset',
                    'jumlah asset',
                    'jumlah aset',
                    'total asset',
                    'total aset',
                ]
            )
        ) {
            return [
                'intent' =>
                    'asset.query',

                'confidence' =>
                    0.65,

                'entities' => [
                    'asset_name' => null,
                    'asset_code' => null,
                    'category' => null,
                    'subcategory' => null,
                    'vendor' => null,
                    'responsible_user' => null,
                    'purchase_price' => null,
                    'condition' => null,
                    'quantity' => null,
                    'date' => null,
                    'status' => null,
                    'search' => null,
                ],

                'missing_fields' => [],

                'reply' =>
                    'Saya akan cek jumlah asset Anda.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Damaged assets
        |--------------------------------------------------------------------------
        */

        if (
            $this->containsAny(
                $normalized,
                [
                    'asset rusak',
                    'aset rusak',
                    'asset yg rusak',
                    'aset yg rusak',
                    'asset yang rusak',
                    'aset yang rusak',
                ]
            )
        ) {
            return [
                'intent' =>
                    'asset.query',

                'confidence' =>
                    0.75,

                'entities' => [
                    'asset_name' => null,
                    'asset_code' => null,
                    'category' => null,
                    'subcategory' => null,
                    'vendor' => null,
                    'responsible_user' => null,
                    'purchase_price' => null,
                    'condition' => 'damaged',
                    'quantity' => null,
                    'date' => null,
                    'status' => null,
                    'search' => null,
                ],

                'missing_fields' => [],

                'reply' =>
                    'Saya akan cek asset yang rusak.',
            ];
        }


        return $this->unknown();
    }


    protected function unknown(): array
    {
        return [
            'intent' =>
                'unknown',

            'confidence' =>
                0,

            'entities' => [
                'asset_name' => null,
                'asset_code' => null,
                'category' => null,
                'subcategory' => null,
                'vendor' => null,
                'responsible_user' => null,
                'purchase_price' => null,
                'condition' => null,
                'quantity' => null,
                'date' => null,
                'status' => null,
                'search' => null,
            ],

            'missing_fields' => [],

            'reply' =>
                'Maaf, saya belum memahami maksudnya. Bisa jelaskan sedikit lagi?',
        ];
    }


    protected function stringOrNull(
        mixed $value
    ): ?string {
        if (
            $value === null
        ) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        return
            $value === ''
                ? null
                : $value;
    }


    protected function numberOrNull(
        mixed $value
    ): ?float {
        if (
            $value === null ||
            $value === ''
        ) {
            return null;
        }

        if (
            !is_numeric($value)
        ) {
            return null;
        }

        return
            (float) $value;
    }


    protected function integerOrNull(
        mixed $value
    ): ?int {
        if (
            $value === null ||
            $value === ''
        ) {
            return null;
        }

        if (
            !is_numeric($value)
        ) {
            return null;
        }

        return
            (int) $value;
    }


    protected function containsAny(
        string $text,
        array $needles
    ): bool {
        foreach (
            $needles as $needle
        ) {
            if (
                str_contains(
                    $text,
                    $needle
                )
            ) {
                return true;
            }
        }

        return false;
    }
}