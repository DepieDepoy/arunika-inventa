<?php

namespace App\Services\AI;

use App\Services\Intelligence\Basic\TeraBasicEngine;
use App\Services\Intelligence\TeraSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Google Gemini (free tier) sebagai "penerjemah" pertanyaan saja.
 *
 * Yang dikirim ke Google HANYA teks pertanyaan user — tidak ada data aset,
 * nama karyawan, atau data perusahaan. Gemini mengembalikan {intent, params},
 * lalu jawabannya tetap diambil dari database Vasetra oleh TeraBasicEngine.
 */
class GeminiClassifier
{
    public function configured(): bool
    {
        return TeraSettings::mode() === 'gemini';
    }

    /**
     * @return array{intent:string, params:array}|null
     */
    public function classify(string $question): ?array
    {
        $key = TeraSettings::geminiKey();
        if (!$key) {
            return null;
        }

        $intents = collect(TeraBasicEngine::INTENTS)->map(fn ($d, $k) => "- {$k}: {$d}")->implode("\n");

        $prompt = <<<TXT
Kamu adalah pengklasifikasi pertanyaan untuk aplikasi manajemen aset perusahaan (Indonesia).
Pertanyaan user bisa memakai singkatan, bahasa gaul, salah ketik, atau campur Inggris.
Pilih SATU intent dari daftar ini:
{$intents}
- unknown: di luar topik aset/maintenance/request/laporan

Isi params hanya bila disebut di pertanyaan:
- category, sub_category, location, holder (nama orang pemegang aset), search (nama/merek barang), code (kode aset)
- condition: "new" | "used"; status: 1 (aktif) | 0 (tidak aktif); broken: true bila menanyakan aset rusak
- period: salah satu "hari ini","kemarin","minggu ini","minggu lalu","minggu depan","bulan ini","bulan lalu","bulan depan","tahun ini","tahun lalu"
- overdue: true bila menanyakan yang terlambat; mine: true bila menanyakan milik/punya "saya"
- request_status: "pending" | "in_progress" | "completed" | "open"

Balas HANYA JSON: {"intent": "...", "params": {...}}

Pertanyaan: {$question}
TXT;

        try {
            $response = Http::timeout(12)
                ->withHeaders(['x-goog-api-key' => $key])
                ->post('https://generativelanguage.googleapis.com/v1beta/models/' . TeraSettings::geminiModel() . ':generateContent', [
                    'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0, 'responseMimeType' => 'application/json', 'maxOutputTokens' => 300],
                ]);

            if (!$response->successful()) {
                Log::info('Gemini gagal.', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 300)]);

                return null;
            }

            $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
            $json = json_decode(trim($text), true);
            $intent = (string) ($json['intent'] ?? 'unknown');

            if (!array_key_exists($intent, TeraBasicEngine::INTENTS)) {
                return null;
            }

            $params = array_intersect_key((array) ($json['params'] ?? []), array_flip([
                'category', 'sub_category', 'location', 'holder', 'search', 'code', 'condition', 'status',
                'broken', 'period', 'overdue', 'mine', 'request_status',
            ]));

            // Bersihkan nilai: hanya teks pendek / boolean / angka.
            $params = array_filter(array_map(fn ($v) => is_string($v) ? mb_substr(trim(strip_tags($v)), 0, 80) : (is_bool($v) || is_int($v) ? $v : null), $params),
                fn ($v) => $v !== null && $v !== '');

            return ['intent' => $intent, 'params' => $params];
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
