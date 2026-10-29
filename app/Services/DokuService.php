<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class DokuService
{
    public function createPayment(array $payload): array
    {
        $clientId = config('services.doku.client_id');
        $secretKey = config('services.doku.secret_key');
        $baseUrl = rtrim(
            config('services.doku.base_url'),
            '/'
        );

        if (!$clientId || !$secretKey) {
            throw new RuntimeException(
                'DOKU Client ID atau Secret Key belum dikonfigurasi.'
            );
        }

        $requestId = (string) Str::uuid();

        // DOKU meminta timestamp UTC dalam format ISO 8601.
        $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');

        $requestTarget = '/checkout/v1/payment';

        // Digest dihitung dari JSON body yang benar-benar dikirim.
        $body = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if ($body === false) {
            throw new RuntimeException(
                'Gagal menyiapkan data pembayaran DOKU.'
            );
        }

        $digest = base64_encode(
            hash('sha256', $body, true)
        );

        $signatureComponents =
            "Client-Id:{$clientId}\n" .
            "Request-Id:{$requestId}\n" .
            "Request-Timestamp:{$timestamp}\n" .
            "Request-Target:{$requestTarget}\n" .
            "Digest:{$digest}";

        $signature = base64_encode(
            hash_hmac(
                'sha256',
                $signatureComponents,
                $secretKey,
                true
            )
        );

        $response = Http::acceptJson()
            ->withHeaders([
                'Client-Id' => $clientId,
                'Request-Id' => $requestId,
                'Request-Timestamp' => $timestamp,
                'Signature' => 'HMACSHA256=' . $signature,
                'Content-Type' => 'application/json',
            ])
            ->withBody($body, 'application/json')
            ->timeout(30)
            ->post($baseUrl . $requestTarget);

        if (!$response->successful()) {
            report(new RuntimeException(
                'DOKU API error: HTTP ' .
                $response->status() .
                ' - ' .
                $response->body()
            ));

            throw new RuntimeException(
                'DOKU gagal membuat pembayaran. Periksa konfigurasi dan log aplikasi.'
            );
        }

        $result = $response->json();

        $paymentUrl = data_get(
            $result,
            'response.payment.url'
        );

        if (!is_array($result) || empty($paymentUrl)) {
            throw new RuntimeException(
                'Respons DOKU tidak memiliki payment URL.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PAYMENT EXPIRY
        |--------------------------------------------------------------------------
        |
        | Format DOKU: yyyyMMddHHmmss
        | Zona waktu: Asia/Jakarta (WIB)
        |--------------------------------------------------------------------------
        */

        $expiredDate = data_get(
            $result,
            'response.payment.expired_date'
        );

        $expiryTime = null;

        if (
            is_string($expiredDate) &&
            preg_match('/^\d{14}$/', $expiredDate)
        ) {
            try {
                $expiryTime = \Carbon\Carbon::createFromFormat(
                    '!YmdHis',
                    $expiredDate,
                    'Asia/Jakarta'
                )->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return [
            'request_id' => $requestId,
            'response' => $result,
            'payment_url' => $paymentUrl,
            'expiry_time' => $expiryTime,
        ];

    }

    public function checkStatus(string $invoiceNumber): array
    {
        $clientId = config('services.doku.client_id');
        $secretKey = config('services.doku.secret_key');

        $baseUrl = rtrim(
            config('services.doku.base_url'),
            '/'
        );

        if (!$clientId || !$secretKey || !$baseUrl) {
            throw new \RuntimeException(
                'Konfigurasi DOKU belum lengkap.'
            );
        }

        $requestId = (string) \Illuminate\Support\Str::uuid();
        $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');

        $requestTarget = '/orders/v1/status/'
            . rawurlencode($invoiceNumber);

        // GET tidak memakai Digest.
        $signatureComponents =
            "Client-Id:{$clientId}\n" .
            "Request-Id:{$requestId}\n" .
            "Request-Timestamp:{$timestamp}\n" .
            "Request-Target:{$requestTarget}";

        $signature = base64_encode(
            hash_hmac(
                'sha256',
                $signatureComponents,
                $secretKey,
                true
            )
        );

        $response = \Illuminate\Support\Facades\Http::acceptJson()
            ->withHeaders([
                'Client-Id' => $clientId,
                'Request-Id' => $requestId,
                'Request-Timestamp' => $timestamp,
                'Signature' => 'HMACSHA256=' . $signature,
            ])
            ->timeout(30)
            ->get($baseUrl . $requestTarget);

        if (!$response->successful()) {
            report(new \RuntimeException(
                'DOKU Check Status gagal: HTTP '
                . $response->status()
                . ' - '
                . $response->body()
            ));

            throw new \RuntimeException(
                'Status pembayaran belum dapat diverifikasi ke DOKU.'
            );
        }

        $result = $response->json();

        if (!is_array($result)) {
            throw new \RuntimeException(
                'Respons Check Status DOKU tidak valid.'
            );
        }

        return $result;
    }

}
