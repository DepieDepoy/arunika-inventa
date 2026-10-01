<?php

namespace App\Services\AI\Providers;

use App\Models\AiProvider;
use App\Services\AI\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiProvider implements AiProviderInterface
{
    public function __construct(
        protected AiProvider $provider
    ) {
    }

    /**
     * Understand natural language using OpenAI Responses API.
     *
     * IMPORTANT:
     * This method ONLY understands the user's request.
     * It never performs database actions.
     */
    public function understand(
        string $prompt,
        array $context = []
    ): array {
        $apiKey = $this->provider->api_key;

        if (!$apiKey) {
            throw new RuntimeException(
                'OpenAI API key belum dikonfigurasi.'
            );
        }

        $model =
            $this->provider->model
            ?: 'gpt-5.6-terra';

        $baseUrl =
            rtrim(
                $this->provider->api_base_url
                    ?: 'https://api.openai.com/v1',
                '/'
            );

        $instructions =
            $this->buildInstructions(
                $context
            );

        $input =
            $this->buildInput(
                $prompt,
                $context
            );

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(60)
            ->post(
                $baseUrl . '/responses',
                [
                    'model' => $model,

                    'instructions' =>
                        $instructions,

                    'input' =>
                        $input,

                    'text' => [
                        'format' => [
                            'type' => 'json_schema',

                            'name' =>
                                'vasetra_ai_understanding',

                            'strict' =>
                                true,

                            'schema' => [
                                'type' => 'object',

                                'additionalProperties' =>
                                    false,

                                'properties' => [

                                    'intent' => [
                                        'type' =>
                                            'string',

                                        'enum' => [
                                            'chat',
                                            'asset.query',
                                            'asset.create',
                                            'asset.edit',
                                            'asset.delete',
                                            'category.query',
                                            'category.create',
                                            'subcategory.query',
                                            'subcategory.create',
                                            'vendor.query',
                                            'vendor.create',
                                            'user.query',
                                            'user.create',
                                            'maintenance.query',
                                            'maintenance.create',
                                            'maintenance.edit',
                                            'maintenance.delete',
                                            'assignment.query',
                                            'assignment.create',
                                            'assignment.return',
                                            'unknown',
                                        ],
                                    ],

                                    'confidence' => [
                                        'type' =>
                                            'number',

                                        'minimum' =>
                                            0,

                                        'maximum' =>
                                            1,
                                    ],

                                    'entities' => [
                                        'type' =>
                                            'object',

                                        'additionalProperties' =>
                                            false,

                                        'properties' => [

                                            'asset_name' => [
                                                'type' =>
                                                    [
                                                        'string',
                                                        'null',
                                                    ],
                                            ],

                                            'asset_code' => [
                                                'type' =>
                                                    [
                                                        'string',
                                                        'null',
                                                    ],
                                            ],

                                            'category' => [
                                                'type' =>
                                                    [
                                                        'string',
                                                        'null',
                                                    ],
                                            ],

                                            'subcategory' => [
                                                'type' =>
                                                    [
                                                        'string',
                                                        'null',
                                                    ],
                                            ],

                                            'vendor' => [
                                                'type' =>
                                                    [
                                                        'string',
                                                        'null',
                                                    ],
                                            ],

                                            'responsible_user' => [
                                                'type' =>
                                                    [
                                                        'string',
                                                        'null',
                                                    ],
                                            ],

                                            'purchase_price' => [
                                                'type' =>
                                                    [
                                                        'number',
                                                        'null',
                                                    ],
                                            ],

                                            'condition' => [
                                                'type' =>
                                                    [
                                                        'string',
                                                        'null',
                                                    ],
                                            ],

                                            'quantity' => [
                                                'type' =>
                                                    [
                                                        'integer',
                                                        'null',
                                                    ],
                                            ],

                                            'date' => [
                                                'type' =>
                                                    [
                                                        'string',
                                                        'null',
                                                    ],
                                            ],

                                            'status' => [
                                                'type' =>
                                                    [
                                                        'string',
                                                        'null',
                                                    ],
                                            ],

                                            'search' => [
                                                'type' =>
                                                    [
                                                        'string',
                                                        'null',
                                                    ],
                                            ],

                                        ],

                                        'required' => [
                                            'asset_name',
                                            'asset_code',
                                            'category',
                                            'subcategory',
                                            'vendor',
                                            'responsible_user',
                                            'purchase_price',
                                            'condition',
                                            'quantity',
                                            'date',
                                            'status',
                                            'search',
                                        ],
                                    ],

                                    'missing_fields' => [
                                        'type' =>
                                            'array',

                                        'items' => [
                                            'type' =>
                                                'string',
                                        ],
                                    ],

                                    'reply' => [
                                        'type' =>
                                            'string',
                                    ],
                                ],

                                'required' => [
                                    'intent',
                                    'confidence',
                                    'entities',
                                    'missing_fields',
                                    'reply',
                                ],
                            ],
                        ],
                    ],
                ]
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenAI API error: ' .
                $response->body()
            );
        }

        $json =
            $response->json();

        $outputText =
            $this->extractOutputText(
                $json
            );

        if (!$outputText) {
            throw new RuntimeException(
                'OpenAI tidak mengembalikan output text.'
            );
        }

        $decoded =
            json_decode(
                $outputText,
                true
            );

        if (
            !is_array($decoded)
        ) {
            throw new RuntimeException(
                'Output AI tidak valid.'
            );
        }

        return $decoded;
    }


    /**
     * Build Tera's main system instructions.
     */
    protected function buildInstructions(
        array $context = []
    ): string {
        return <<<PROMPT
Kamu adalah Tera, AI Assistant resmi untuk aplikasi Vasetra.

Vasetra adalah aplikasi manajemen aset perusahaan.

Tugas utama kamu adalah memahami bahasa manusia.
Jangan hanya mencocokkan keyword.

Kamu harus memahami:

- Bahasa Indonesia formal.
- Bahasa Indonesia sehari-hari.
- Bahasa chat.
- Singkatan.
- Typo ringan.
- Bahasa campuran Indonesia dan English.
- Angka yang disingkat.
- Konteks percakapan.
- Maksud pengguna walaupun kalimat tidak lengkap.

Contoh singkatan:

"brp" = berapa
"yg" = yang
"gk" = tidak
"ga" = tidak
"nggak" = tidak
"blm" = belum
"gw" = saya
"gue" = saya
"aku" = saya
"sy" = saya
"udh" = sudah
"sdh" = sudah
"bnyk" = banyak
"jml" = jumlah
"ast" = asset
"aset" = asset
"pic" = responsible user / penanggung jawab
"12jt" = 12000000
"12 jt" = 12000000
"12juta" = 12000000
"500rb" = 500000
"500 rbu" = 500000

Contoh:

"brp aset gw?"
berarti pertanyaan jumlah asset milik user saat ini.

"brp aset yg rusak?"
berarti pertanyaan jumlah asset dengan kondisi rusak.

"cek aset yg blm ada pic"
berarti mencari asset yang responsible_user-nya kosong.

"maintenance yg telat ada?"
berarti mencari maintenance yang sudah lewat tanggal dan masih aktif.

"tambah dell 12jt"
berarti kemungkinan besar user ingin membuat asset baru dengan nama Dell dan harga 12000000, tetapi data lain mungkin masih diperlukan.

"masukin laptop dell 12 juta"
berarti kemungkinan intent asset.create.

"bisa tambahin asset ini?"
berarti intent asset.create jika konteks sebelumnya membahas asset.

Jangan mengarang data yang tidak disebut user.

Jangan membuat asset_code sendiri.
Asset code dibuat oleh Laravel.

Jangan membuat company_id.
Company ID berasal dari authenticated user.

Jangan membuat database ID.
ID berasal dari Laravel.

Jangan menganggap permission user.
Permission diperiksa Laravel.

Jangan menganggap subscription user.
Subscription diperiksa Laravel.

Kamu hanya memahami maksud.
Laravel yang mengambil keputusan keamanan dan database.

Jika informasi tidak cukup untuk menentukan intent,
gunakan intent "unknown" atau "chat".

Jika user meminta tindakan yang mengubah data,
tentukan intent action yang sesuai.

Jika user hanya bertanya,
gunakan intent query yang sesuai.

Jika user hanya bercakap-cakap,
gunakan intent chat.

Jawaban reply harus singkat, natural, ramah, dan menggunakan bahasa Indonesia.

Jangan mengatakan bahwa kamu telah melakukan tindakan database
jika Laravel belum melakukan tindakan tersebut.

Konteks aplikasi:
{$this->contextSummary($context)}
PROMPT;
    }


    /**
     * Build user input.
     */
    protected function buildInput(
        string $prompt,
        array $context = []
    ): array {
        $history =
            $context['conversation_history'] ?? [];

        $items = [];

        foreach (
            array_slice(
                $history,
                -8
            ) as $message
        ) {
            if (
                !isset(
                    $message['role'],
                    $message['content']
                )
            ) {
                continue;
            }

            $items[] = [
                'role' =>
                    $message['role'],

                'content' =>
                    $message['content'],
            ];
        }

        $items[] = [
            'role' =>
                'user',

            'content' =>
                $prompt,
        ];

        return $items;
    }


    /**
     * Context summary passed to AI.
     */
    protected function contextSummary(
        array $context
    ): string {
        $summary = [];

        if (
            isset(
                $context['current_page']
            )
        ) {
            $summary[] =
                'Current page: ' .
                $context['current_page'];
        }

        if (
            isset(
                $context['user_role']
            )
        ) {
            $summary[] =
                'User role: ' .
                $context['user_role'];
        }

        if (
            isset(
                $context['permissions']
            )
        ) {
            $summary[] =
                'Available permissions: ' .
                implode(
                    ', ',
                    $context['permissions']
                );
        }

        if (
            isset(
                $context['company_name']
            )
        ) {
            $summary[] =
                'Company: ' .
                $context['company_name'];
        }

        if (
            empty($summary)
        ) {
            return 'Tidak ada konteks tambahan.';
        }

        return implode(
            "\n",
            $summary
        );
    }


    /**
     * Extract output text from Responses API response.
     */
    protected function extractOutputText(
        array $response
    ): ?string {
        if (
            isset(
                $response['output_text']
            ) &&
            is_string(
                $response['output_text']
            )
        ) {
            return
                $response['output_text'];
        }

        foreach (
            $response['output'] ?? []
            as $output
        ) {
            foreach (
                $output['content'] ?? []
                as $content
            ) {
                if (
                    ($content['type'] ?? null)
                    === 'output_text'
                    &&
                    isset(
                        $content['text']
                    )
                ) {
                    return
                        $content['text'];
                }
            }
        }

        return null;
    }


    public function providerName(): string
    {
        return 'OpenAI';
    }


    public function model(): ?string
    {
        return $this->provider->model;
    }
}