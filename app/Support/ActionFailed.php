<?php

namespace App\Support;

use RuntimeException;

/**
 * Aksi bisnis yang ditolak (pesan untuk pengguna + kode error API + status HTTP).
 * Dipakai service yang dipanggil dari web maupun API mobile.
 */
class ActionFailed extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'VALIDATION_ERROR',
        public readonly int $status = 422,
    ) {
        parent::__construct($message);
    }
}
