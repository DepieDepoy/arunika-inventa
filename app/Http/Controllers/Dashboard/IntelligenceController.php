<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Intelligence\VasetraAiActionExecutor;
use App\Services\Intelligence\VasetraIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class IntelligenceController extends Controller
{
    /**
     * Ask Vasetra AI.
     */
    public function ask(
        Request $request,
        VasetraIntelligenceService $intelligence
    ): JsonResponse {
        $validated = $request->validate([
            'question' => [
                'required',
                'string',
                'max:500',
            ],
        ]);

        $user = Auth::user();

        $result = $intelligence->ask(
            $validated['question'],
            (int) $user->company_id,
            (int) $user->id
        );

        return response()->json(
            $result
        );
    }

    /**
     * Confirm and execute AI action.
     */
    public function confirm(
        Request $request,
        VasetraAiActionExecutor $executor
    ): JsonResponse {
        $validated = $request->validate([
            'token' => [
                'required',
                'string',
                'size:64',
            ],
        ]);

        $user = Auth::user();

        try {
            $result = $executor->execute(
                $user,
                $validated['token']
            );

            return response()->json(
                $result
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,

                'type' =>
                    'action_failed',

                'message' =>
                    $e->getMessage(),
            ], 422);
        }
    }
}