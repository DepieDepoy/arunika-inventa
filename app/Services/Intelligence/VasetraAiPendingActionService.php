<?php

namespace App\Services\Intelligence;

use App\Models\AiPendingAction;
use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

class VasetraAiPendingActionService
{
    /**
     * Create pending action.
     */
    public function create(
        User $user,
        string $action,
        array $payload
    ): AiPendingAction {
        /*
        |--------------------------------------------------------------------------
        | Remove old pending actions for same user
        |--------------------------------------------------------------------------
        */

        AiPendingAction::query()
            ->where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->where('status', 'pending')
            ->update([
                'status' => 'cancelled',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Create token
        |--------------------------------------------------------------------------
        */

        return AiPendingAction::create([
            'user_id' =>
                $user->id,

            'company_id' =>
                $user->company_id,

            'action' =>
                $action,

            'token' =>
                Str::random(64),

            'payload' =>
                $payload,

            'status' =>
                'pending',

            /*
             * Confirmation hanya valid 5 menit.
             */
            'expires_at' =>
                now()->addMinutes(5),
        ]);
    }

    /**
     * Find pending action securely.
     */
    public function findForUser(
        User $user,
        string $token
    ): AiPendingAction {
        $pending = AiPendingAction::query()
            ->where('token', $token)
            ->where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->where('status', 'pending')
            ->first();

        if (!$pending) {
            throw new RuntimeException(
                'Action tidak ditemukan atau sudah tidak berlaku.'
            );
        }

        if ($pending->expires_at->isPast()) {
            $pending->update([
                'status' => 'expired',
            ]);

            throw new RuntimeException(
                'Konfirmasi sudah kedaluwarsa. '
                . 'Silakan ulangi perintah AI.'
            );
        }

        return $pending;
    }

    /**
     * Mark action as confirmed.
     */
    public function markConfirmed(
        AiPendingAction $pending
    ): void {
        $pending->update([
            'status' =>
                'confirmed',

            'confirmed_at' =>
                now(),
        ]);
    }

    /**
     * Cancel action.
     */
    public function cancel(
        AiPendingAction $pending
    ): void {
        $pending->update([
            'status' =>
                'cancelled',
        ]);
    }
}