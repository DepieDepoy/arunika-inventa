<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function createTransaction(array $params): array
    {
        $result = Snap::createTransaction($params);

        return [
            'token' => $result->token ?? null,
            'redirect_url' => $result->redirect_url ?? null,
        ];
    }
}