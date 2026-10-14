<?php

namespace App\Services\Intelligence;

use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\User;
use App\Services\Asset\AssetCreateAction;
use Illuminate\Support\Facades\Auth;
use Throwable;

class VasetraIntelligenceService
{
    public function __construct(
        protected VasetraAiUnderstandingService $understanding,
        protected VasetraAiPendingActionService $pendingActions
    ) {
    }


    /**
     * Main Tera entry point.
     */
    public function ask(
        string $question,
        int $companyId,
        int $userId
    ): array {

        $user =
            User::query()
                ->whereKey($userId)
                ->where(
                    'company_id',
                    $companyId
                )
                ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Build context
        |--------------------------------------------------------------------------
        */

        $context =
            $this->buildContext(
                $user
            );


        /*
        |--------------------------------------------------------------------------
        | AI UNDERSTANDING
        |--------------------------------------------------------------------------
        */

        $understanding =
            $this->understanding->understand(
                $question,
                $context
            );


        $intent =
            $understanding['intent']
            ?? 'unknown';


        $entities =
            $understanding['entities']
            ?? [];


        /*
        |--------------------------------------------------------------------------
        | ACTION: ASSET CREATE
        |--------------------------------------------------------------------------
        */

        if (
            $intent ===
            'asset.create'
        ) {
            return $this->handleAssetCreate(
                $user,
                $entities,
                $understanding
            );
        }


        /*
        |--------------------------------------------------------------------------
        | QUERY: ASSET
        |--------------------------------------------------------------------------
        */

        if (
            $intent ===
            'asset.query'
        ) {
            return $this->handleAssetQuery(
                $user,
                $entities,
                $understanding
            );
        }


        /*
        |--------------------------------------------------------------------------
        | QUERY: MAINTENANCE
        |--------------------------------------------------------------------------
        */

        if (
            $intent ===
            'maintenance.query'
        ) {
            return $this->handleMaintenanceQuery(
                $user,
                $entities,
                $understanding
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHAT
        |--------------------------------------------------------------------------
        */

        if (
            $intent ===
            'chat'
        ) {
            return [
                'success' =>
                    true,

                'type' =>
                    'answer',

                'intent' =>
                    'chat',

                'message' =>
                    $understanding['reply']
                    ?: 'Halo, saya Tera. Ada yang bisa saya bantu?',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | UNKNOWN
        |--------------------------------------------------------------------------
        */

        if (
            $intent ===
            'unknown'
        ) {
            return [
                'success' =>
                    true,

                'type' =>
                    'answer',

                'intent' =>
                    'unknown',

                'message' =>
                    $understanding['reply']
                    ?: 'Maaf, saya belum memahami maksud Anda.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | OTHER ACTIONS - FUTURE
        |--------------------------------------------------------------------------
        */

        return [
            'success' =>
                true,

            'type' =>
                'answer',

            'intent' =>
                $intent,

            'message' =>
                $understanding['reply']
                ?: 'Saya memahami permintaannya, tetapi fitur tersebut belum diaktifkan.',
        ];
    }


    /**
     * Asset creation flow.
     */
    protected function handleAssetCreate(
        User $user,
        array $entities,
        array $understanding
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Permission check
        |--------------------------------------------------------------------------
        */

        $guard =
            app(
                VasetraAiActionGuard::class
            );


        $permission =
            $guard->check(
                $user,
                'asset.create'
            );


        if (
            !$permission['allowed']
        ) {
            return [
                'success' =>
                    false,

                'type' =>
                    'permission_denied',

                'action' =>
                    'asset.create',

                'message' =>
                    $permission['message'],
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Required fields
        |--------------------------------------------------------------------------
        */

        $required = [
            'asset_name' =>
                'Nama asset',

            'category' =>
                'Category',

            'subcategory' =>
                'Subcategory',

            'vendor' =>
                'Vendor',

            'purchase_price' =>
                'Harga',
        ];


        $missing = [];


        foreach (
            $required as $field => $label
        ) {
            if (
                empty(
                    $entities[$field]
                )
                &&
                !(
                    $field ===
                    'purchase_price'
                    &&
                    array_key_exists(
                        $field,
                        $entities
                    )
                    &&
                    $entities[$field] === 0
                )
            ) {
                $missing[] =
                    $label;
            }
        }


        if (
            !empty($missing)
        ) {
            return [
                'success' =>
                    true,

                'type' =>
                    'missing_fields',

                'action' =>
                    'asset.create',

                'message' =>
                    $this->buildMissingFieldMessage(
                        $missing,
                        $entities
                    ),

                'data' =>
                    $entities,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Prepare preview payload
        |--------------------------------------------------------------------------
        */

        $payload = [

            'asset_name' =>
                trim(
                    $entities['asset_name']
                ),

            'category' =>
                trim(
                    $entities['category']
                ),

            'subcategory' =>
                trim(
                    $entities['subcategory']
                ),

            'vendor' =>
                trim(
                    $entities['vendor']
                ),

            'purchase_price' =>
                (float)
                $entities['purchase_price'],
        ];


        if (
            !empty(
                $entities['responsible_user']
            )
        ) {
            $payload[
                'responsible_user'
            ] =
                trim(
                    $entities['responsible_user']
                );
        }


        if (
            !empty(
                $entities['condition']
            )
        ) {
            $payload[
                'asset_condition'
            ] =
                trim(
                    $entities['condition']
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Existing AssetCreateAction
        |--------------------------------------------------------------------------
        */

        $action =
            app(
                \App\Services\Intelligence\Actions\AssetCreateAction::class
            );


        $preview =
            $action->preview(
                $user,
                $payload
            );


        if (
            ($preview['type'] ?? null)
            === 'confirmation_required'
        ) {

            $pending =
                $this->pendingActions->create(
                    $user,
                    'asset.create',
                    $preview['data']
                );


            return [
                'success' =>
                    true,

                'type' =>
                    'confirmation_required',

                'action' =>
                    'asset.create',

                'message' =>
                    'Data asset sudah lengkap. Silakan periksa dan konfirmasi sebelum saya membuat asset.',

                'token' =>
                    $pending->token,

                'expires_at' =>
                    $pending
                        ->expires_at
                        ?->toIso8601String(),

                'data' =>
                    $preview['data'],
            ];
        }


        return $preview;
    }


    /**
     * Asset information query.
     */
    protected function handleAssetQuery(
        User $user,
        array $entities,
        array $understanding
    ): array {

        $query =
            Asset::query()
                ->where(
                    'company_id',
                    $user->company_id
                );


        /*
        |--------------------------------------------------------------------------
        | My assets
        |--------------------------------------------------------------------------
        */

        $responsible =
            strtolower(
                (string) (
                    $entities[
                        'responsible_user'
                    ] ?? ''
                )
            );


        if (
            in_array(
                $responsible,
                [
                    'saya',
                    'aku',
                    'gw',
                    'gue',
                    'my',
                    'me',
                ],
                true
            )
        ) {
            $query->where(
                'responsible_user_id',
                $user->id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Condition
        |--------------------------------------------------------------------------
        */

        $condition =
            $entities['condition']
            ?? null;


        if (
            $condition
        ) {

            $condition =
                $this->normalizeCondition(
                    $condition
                );


            if (
                $condition
            ) {
                $query->where(
                    'asset_condition',
                    $condition
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        $search =
            $entities['search']
            ?? null;


        if (
            $search
        ) {

            $query->where(
                function ($q) use ($search) {

                    $q->where(
                        'asset_name',
                        'like',
                        '%' .
                        $search .
                        '%'
                    );

                    $q->orWhere(
                        'asset_code',
                        'like',
                        '%' .
                        $search .
                        '%'
                    );

                }
            );
        }


        $total =
            $query->count();


        /*
        |--------------------------------------------------------------------------
        | Query response
        |--------------------------------------------------------------------------
        */

        $reply =
            $this->buildAssetQueryReply(
                $total,
                $entities,
                $understanding
            );


        return [
            'success' =>
                true,

            'type' =>
                'answer',

            'intent' =>
                'asset.query',

            'data' => [
                'total' =>
                    $total,
            ],

            'message' =>
                $reply,
        ];
    }


    /**
     * Maintenance query.
     */
    protected function handleMaintenanceQuery(
        User $user,
        array $entities,
        array $understanding
    ): array {

        $query =
            Maintenance::query()
                ->where(
                    'company_id',
                    $user->company_id
                )
                ->whereIn(
                    'status',
                    [
                        'scheduled',
                        'in_progress',
                    ]
                );


        $today =
            now()->startOfDay();


        $condition =
            strtolower(
                (string) (
                    $entities['status']
                    ?? ''
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Overdue
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $condition,
                [
                    'overdue',
                    'terlambat',
                    'telat',
                    'over due',
                ],
                true
            )
        ) {

            $query->whereDate(
                'maintenance_date',
                '<',
                $today
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Today
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $condition,
                [
                    'today',
                    'hari ini',
                ],
                true
            )
        ) {

            $query->whereDate(
                'maintenance_date',
                $today
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Count
        |--------------------------------------------------------------------------
        */

        $total =
            $query->count();


        if (
            in_array(
                $condition,
                [
                    'overdue',
                    'terlambat',
                    'telat',
                    'over due',
                ],
                true
            )
        ) {

            $message =
                "Saat ini ada {$total} maintenance yang terlambat.";
        } elseif (
            in_array(
                $condition,
                [
                    'today',
                    'hari ini',
                ],
                true
            )
        ) {

            $message =
                "Hari ini ada {$total} maintenance.";
        } else {

            $message =
                "Saat ini ada {$total} maintenance aktif.";
        }


        return [
            'success' =>
                true,

            'type' =>
                'answer',

            'intent' =>
                'maintenance.query',

            'data' => [
                'total' =>
                    $total,
            ],

            'message' =>
                $message,
        ];
    }


    /**
     * Missing field message.
     */
    protected function buildMissingFieldMessage(
        array $missing,
        array $entities
    ): string {

        $message =
            'Siap 👍 Saya bisa bantu tambahkan asset tersebut.';


        if (
            !empty(
                $entities['asset_name']
            )
        ) {
            $message .=
                "\n\nAsset: " .
                $entities['asset_name'];
        }


        if (
            !empty(
                $entities['purchase_price']
            )
        ) {
            $message .=
                "\nHarga: Rp" .
                number_format(
                    (float)
                    $entities['purchase_price'],
                    0,
                    ',',
                    '.'
                );
        }


        $message .=
            "\n\nSaya masih membutuhkan:";


        foreach (
            $missing as $item
        ) {
            $message .=
                "\n• " .
                $item;
        }


        $message .=
            "\n\nContoh: Laptop / Notebook / Dell";


        return $message;
    }


    /**
     * Asset query response.
     */
    protected function buildAssetQueryReply(
        int $total,
        array $entities,
        array $understanding
    ): string {

        $condition =
            $entities['condition']
            ?? null;


        $responsible =
            $entities['responsible_user']
            ?? null;


        if (
            $responsible
            &&
            in_array(
                strtolower(
                    $responsible
                ),
                [
                    'saya',
                    'aku',
                    'gw',
                    'gue',
                ],
                true
            )
        ) {
            return
                "Saat ini ada {$total} asset yang menjadi tanggung jawab Anda.";
        }


        if (
            $condition
        ) {

            $label =
                $this->conditionLabel(
                    $condition
                );

            return
                "Saat ini ada {$total} asset dengan kondisi {$label}.";
        }


        return
            "Saat ini terdapat {$total} asset di perusahaan Anda.";
    }


    protected function normalizeCondition(
        string $condition
    ): ?string {

        $condition =
            strtolower(
                trim($condition)
            );


        return match ($condition) {

            'rusak',
            'damage',
            'damaged',
            'broken' =>
                'damaged',

            'baik',
            'good' =>
                'good',

            'baru',
            'new' =>
                'new',

            'maintenance',
            'perawatan' =>
                'maintenance',

            default =>
                $condition,
        };
    }


    protected function conditionLabel(
        string $condition
    ): string {

        return match (
            strtolower(
                trim($condition)
            )
        ) {

            'damaged' =>
                'rusak',

            'good' =>
                'baik',

            'new' =>
                'baru',

            'maintenance' =>
                'maintenance',

            default =>
                $condition,
        };
    }


    /**
     * Build application context for Tera.
     */
    protected function buildContext(
        User $user
    ): array {

        $permissions = [];


        /*
        |--------------------------------------------------------------------------
        | Known Vasetra AI permissions
        |--------------------------------------------------------------------------
        */

        $knownPermissions = [

            'asset.view',
            'asset.create',
            'asset.edit',
            'asset.delete',
            'asset.import',

            'category.view',
            'category.create',
            'category.edit',

            'subcategory.view',
            'subcategory.create',

            'vendor.view',
            'vendor.create',

            'user.view',
            'user.create',

            'maintenance.view',
            'maintenance.create',
            'maintenance.edit',
            'maintenance.delete',
        ];


        foreach (
            $knownPermissions as $permission
        ) {

            if (
                $user->hasPermission(
                    $permission
                )
            ) {
                $permissions[] =
                    $permission;
            }
        }


        return [

            'current_page' =>
                request()->path(),

            'user_role' =>
                $user->role?->role_name,

            'company_name' =>
                $user->company?->company_name,

            'permissions' =>
                $permissions,

            'conversation_history' =>
                [],
        ];
    }
}