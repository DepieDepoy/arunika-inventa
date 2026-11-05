<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Dashboard\IntelligenceController as WebIntelligenceController;
use App\Services\Intelligence\VasetraAiActionExecutor;
use App\Services\Intelligence\VasetraIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vasetra AI (asisten) — sama persis dengan panel AI di dashboard web.
 *
 * Alur aksi: jawaban `ask` dapat berisi aksi yang menunggu konfirmasi
 * (mis. membuat jadwal) beserta `token`. Tampilkan ringkasannya ke user,
 * lalu kirim token ke `confirm` bila user menyetujui.
 */
class IntelligenceController extends ApiController
{
    /**
     * Body: question (maks. 500 karakter)
     */
    public function ask(Request $request, VasetraIntelligenceService $intelligence): JsonResponse
    {
        return $this->delegate(
            fn () => app(WebIntelligenceController::class)->ask($request, $intelligence),
            'AI_FAILED'
        );
    }

    /**
     * Body: token (64 karakter, dari respons ask)
     */
    public function confirm(Request $request, VasetraAiActionExecutor $executor): JsonResponse
    {
        return $this->delegate(
            fn () => app(WebIntelligenceController::class)->confirm($request, $executor),
            'AI_ACTION_FAILED'
        );
    }
}
