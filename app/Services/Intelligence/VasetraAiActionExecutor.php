<?php

namespace App\Services\Intelligence;

use App\Models\User;
use App\Services\Asset\AssetCreationService;
use RuntimeException;

class VasetraAiActionExecutor
{
    public function __construct(
        protected VasetraAiActionGuard $guard,
        protected AssetCreationService $assetCreationService,
        protected VasetraAiPendingActionService $pendingActions
    ) {
    }

    /**
     * Execute a confirmed AI action.
     */
    public function execute(
        User $user,
        string $token
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Find pending action
        |--------------------------------------------------------------------------
        */

        $pending =
            $this->pendingActions->findForUser(
                $user,
                $token
            );

        /*
        |--------------------------------------------------------------------------
        | ACTION MAP
        |--------------------------------------------------------------------------
        */

        $action = $pending->action;

        $actionConfig =
            VasetraAiActionMap::get(
                $action
            );

        if (!$actionConfig) {
            throw new RuntimeException(
                "AI action [{$action}] tidak dikenal."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PERMISSION CHECK AGAIN
        |--------------------------------------------------------------------------
        |
        | Jangan percaya permission check yang dilakukan
        | saat preview.
        |
        | User bisa saja kehilangan permission setelah preview.
        |
        */

        $permissionResult =
            $this->guard->check(
                $user,
                $actionConfig['permission']
            );

        if (!$permissionResult['allowed']) {
            throw new RuntimeException(
                $permissionResult['message']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | EXECUTE ACTION
        |--------------------------------------------------------------------------
        */

        $payload =
            $pending->payload;

        $result = match ($action) {

            'asset.create' =>
                $this->createAsset(
                    $user,
                    $payload
                ),

            default =>
                throw new RuntimeException(
                    "AI action [{$action}] belum memiliki executor."
                ),
        };

        /*
        |--------------------------------------------------------------------------
        | MARK CONFIRMED
        |--------------------------------------------------------------------------
        */

        $this->pendingActions->markConfirmed(
            $pending
        );

        return $result;
    }

    /**
     * Execute asset.create.
     */
    protected function createAsset(
        User $user,
        array $payload
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Final payload validation
        |--------------------------------------------------------------------------
        */

        if (
            empty($payload['asset_name']) ||
            empty($payload['category_id']) ||
            empty($payload['vendor_id']) ||
            !array_key_exists(
                'purchase_price',
                $payload
            )
        ) {
            throw new RuntimeException(
                'Data asset tidak lengkap.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Final service
        |--------------------------------------------------------------------------
        |
        | AssetCreationService melakukan:
        |
        | - subscription limit
        | - company scope
        | - category scope
        | - subcategory scope
        | - vendor scope
        | - responsible user scope
        | - asset code generation
        | - QR token
        | - database transaction
        |
        */

        $asset =
            $this->assetCreationService->create(
                $user,
                $payload
            );

        return [
            'success' =>
                true,

            'type' =>
                'action_completed',

            'action' =>
                'asset.create',

            'message' =>
                'Asset berhasil ditambahkan.',

            'data' => [
                'id' =>
                    $asset->id,

                'asset_code' =>
                    $asset->asset_code,

                'asset_name' =>
                    $asset->asset_name,

                'category' =>
                    $asset->category?->category_name,

                'subcategory' =>
                    $asset->subCategory?->sub_category_name,

                'vendor' =>
                    $asset->vendor?->vendor_name,

                'purchase_price' =>
                    $asset->purchase_price,
            ],
        ];
    }
}