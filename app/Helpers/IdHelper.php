<?php

use Illuminate\Support\Facades\Crypt;

/*
|--------------------------------------------------------------------------
| Vasetra Encrypted ID Helper
|--------------------------------------------------------------------------
|
| Fungsi:
|
|   encryptId(1)
|       ↓
|   random encrypted string
|
|   decryptId('...')
|       ↓
|   1
|
| Karakteristik:
| - Tidak membutuhkan database mapping
| - Tidak membutuhkan package tambahan
| - ID yang sama menghasilkan ciphertext berbeda
| - Ciphertext dapat dikembalikan ke ID asli
| - Menggunakan APP_KEY Laravel
| - URL-safe
|
*/


/*
|--------------------------------------------------------------------------
| Encrypt ID
|--------------------------------------------------------------------------
*/

if (! function_exists('encryptId')) {

    function encryptId(int|string|null $id): ?string
    {
        if ($id === null || $id === '') {
            return null;
        }

        $id = (int) $id;

        if ($id <= 0) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Nonce random 8 bytes
        |--------------------------------------------------------------------------
        |
        | Nonce membuat ID yang sama menghasilkan ciphertext berbeda.
        |
        */

        $nonce = random_bytes(8);


        /*
        |--------------------------------------------------------------------------
        | Secret
        |--------------------------------------------------------------------------
        |
        | APP_KEY Laravel menjadi secret utama.
        |
        */

        $secret = (string) config('app.key');

        if ($secret === '') {
            throw new RuntimeException(
                'APP_KEY belum tersedia.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | ID menjadi binary 8 bytes
        |--------------------------------------------------------------------------
        */

        $idBinary = pack('J', $id);


        /*
        |--------------------------------------------------------------------------
        | Generate encryption key dari APP_KEY + nonce
        |--------------------------------------------------------------------------
        */

        $streamKey = hash_hmac(
            'sha256',
            'vasetra-id-encryption|' . $nonce,
            $secret,
            true
        );


        /*
        |--------------------------------------------------------------------------
        | Ambil 8 bytes pertama
        |--------------------------------------------------------------------------
        */

        $stream = substr($streamKey, 0, 8);


        /*
        |--------------------------------------------------------------------------
        | XOR ID dengan stream
        |--------------------------------------------------------------------------
        */

        $encryptedId = '';

        for ($i = 0; $i < 8; $i++) {

            $encryptedId .= chr(
                ord($idBinary[$i]) ^
                ord($stream[$i])
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Signature / authentication tag
        |--------------------------------------------------------------------------
        |
        | Memastikan ciphertext tidak dimodifikasi.
        |
        */

        $signature = hash_hmac(
            'sha256',
            'vasetra-id-signature|' .
            $nonce .
            $encryptedId,
            $secret,
            true
        );


        /*
        |--------------------------------------------------------------------------
        | Gunakan 8 bytes signature
        |--------------------------------------------------------------------------
        */

        $signature = substr($signature, 0, 8);


        /*
        |--------------------------------------------------------------------------
        | Payload
        |--------------------------------------------------------------------------
        |
        | 8 bytes nonce
        | 8 bytes encrypted ID
        | 8 bytes signature
        |
        | Total = 24 bytes
        |
        */

        $payload =
            $nonce .
            $encryptedId .
            $signature;


        /*
        |--------------------------------------------------------------------------
        | Base64 URL Safe
        |--------------------------------------------------------------------------
        */

        return rtrim(
            strtr(
                base64_encode($payload),
                '+/',
                '-_'
            ),
            '='
        );
    }
}


/*
|--------------------------------------------------------------------------
| Decrypt ID
|--------------------------------------------------------------------------
*/

if (! function_exists('decryptId')) {

    function decryptId(string|int|null $encryptedId): ?int
    {
        if ($encryptedId === null || $encryptedId === '') {
            return null;
        }

        if (! is_string($encryptedId)) {
            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | Base64 URL Safe → Base64 normal
        |--------------------------------------------------------------------------
        */

        $base64 = strtr(
            $encryptedId,
            '-_',
            '+/'
        );


        /*
        |--------------------------------------------------------------------------
        | Tambahkan padding
        |--------------------------------------------------------------------------
        */

        $padding = strlen($base64) % 4;

        if ($padding !== 0) {

            $base64 .= str_repeat(
                '=',
                4 - $padding
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Decode Base64
        |--------------------------------------------------------------------------
        */

        $payload = base64_decode(
            $base64,
            true
        );

        if ($payload === false) {
            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | Payload harus tepat 24 bytes
        |--------------------------------------------------------------------------
        */

        if (strlen($payload) !== 24) {
            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | Pecah payload
        |--------------------------------------------------------------------------
        */

        $nonce = substr(
            $payload,
            0,
            8
        );

        $encryptedId = substr(
            $payload,
            8,
            8
        );

        $receivedSignature = substr(
            $payload,
            16,
            8
        );


        /*
        |--------------------------------------------------------------------------
        | Secret
        |--------------------------------------------------------------------------
        */

        $secret = (string) config('app.key');

        if ($secret === '') {
            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | Hitung signature ulang
        |--------------------------------------------------------------------------
        */

        $expectedSignature = hash_hmac(
            'sha256',
            'vasetra-id-signature|' .
            $nonce .
            $encryptedId,
            $secret,
            true
        );


        $expectedSignature = substr(
            $expectedSignature,
            0,
            8
        );


        /*
        |--------------------------------------------------------------------------
        | Validasi signature
        |--------------------------------------------------------------------------
        */

        if (! hash_equals(
            $expectedSignature,
            $receivedSignature
        )) {
            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | Generate stream yang sama
        |--------------------------------------------------------------------------
        */

        $streamKey = hash_hmac(
            'sha256',
            'vasetra-id-encryption|' . $nonce,
            $secret,
            true
        );


        $stream = substr(
            $streamKey,
            0,
            8
        );


        /*
        |--------------------------------------------------------------------------
        | XOR kembali
        |--------------------------------------------------------------------------
        */

        $idBinary = '';

        for ($i = 0; $i < 8; $i++) {

            $idBinary .= chr(
                ord($encryptedId[$i]) ^
                ord($stream[$i])
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Binary → integer
        |--------------------------------------------------------------------------
        */

        $decoded = unpack(
            'Jid',
            $idBinary
        );

        if (! $decoded || ! isset($decoded['id'])) {
            return null;
        }


        $id = (int) $decoded['id'];


        /*
        |--------------------------------------------------------------------------
        | Validasi ID
        |--------------------------------------------------------------------------
        */

        if ($id <= 0) {
            return null;
        }


        return $id;
    }
}
