<?php

namespace App\Services\Intelligence\Actions;

use App\Helpers\PlanLimitHelper;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Intelligence\VasetraAiActionGuard;
use Illuminate\Support\Facades\Validator;

class AssetCreateAction
{
    public function __construct(
        protected VasetraAiActionGuard $guard
    ) {
    }

    /**
     * Preview asset creation.
     *
     * IMPORTANT:
     * Method ini TIDAK membuat asset.
     * Hanya melakukan:
     *
     * - permission check
     * - validation
     * - subscription limit
     * - category resolver
     * - subcategory resolver
     * - vendor resolver
     * - price normalization
     * - preparation untuk confirmation
     */
    public function preview(
        User $user,
        array $data
    ): array {
        /*
        |--------------------------------------------------------------------------
        | PERMISSION
        |--------------------------------------------------------------------------
        */

        $permission =
            $this->guard->check(
                $user,
                'asset.create'
            );

        if (!$permission['allowed']) {
            return [
                'success' =>
                    false,

                'type' =>
                    'permission_denied',

                'message' =>
                    $permission['message'],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | COMPANY
        |--------------------------------------------------------------------------
        */

        $companyId =
            (int) $user->company_id;

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validator = Validator::make(
            $data,
            [
                'asset_name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'category' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'subcategory' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'vendor' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'purchase_price' => [
                    'required',
                ],
            ],
            [
                'asset_name.required' =>
                    'Nama asset wajib diisi.',

                'category.required' =>
                    'Category wajib diisi.',

                'subcategory.required' =>
                    'Subcategory wajib diisi.',

                'vendor.required' =>
                    'Vendor wajib diisi.',

                'purchase_price.required' =>
                    'Harga pembelian wajib diisi.',
            ]
        );

        if ($validator->fails()) {
            return [
                'success' =>
                    false,

                'type' =>
                    'missing_fields',

                'message' =>
                    'Data asset belum lengkap.',

                'errors' =>
                    $validator
                        ->errors()
                        ->toArray(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDATED DATA
        |--------------------------------------------------------------------------
        */

        $validated =
            $validator->validated();

        /*
        |--------------------------------------------------------------------------
        | SUBSCRIPTION LIMIT
        |--------------------------------------------------------------------------
        */

        if (
            !PlanLimitHelper::canAddAssets(1)
        ) {
            $limit =
                PlanLimitHelper::maxAssets();

            $current =
                PlanLimitHelper::currentAssets();

            return [
                'success' =>
                    false,

                'type' =>
                    'subscription_limit',

                'message' =>
                    "Batas asset pada paket Anda "
                    . "adalah {$limit} asset. "
                    . "Saat ini sudah terdapat "
                    . "{$current} asset. "
                    . "Silakan upgrade subscription "
                    . "untuk menambah asset.",

                'limit' =>
                    $limit,

                'current' =>
                    $current,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | CATEGORY
        |--------------------------------------------------------------------------
        */

        $categoryResult =
            $this->resolveCategory(
                $companyId,
                (string) $validated['category']
            );

        if (!$categoryResult['found']) {
            $canCreate =
                $this->guard->can(
                    $user,
                    'category.create'
                );

            return [
                'success' =>
                    false,

                'type' =>
                    'category_not_found',

                'message' =>
                    'Category "'
                    . $validated['category']
                    . '" belum ditemukan.',

                'category' =>
                    $validated['category'],

                'can_create' =>
                    $canCreate,

                'required_permission' =>
                    'category.create',
            ];
        }

        $category =
            $categoryResult['model'];

        /*
        |--------------------------------------------------------------------------
        | SUBCATEGORY
        |--------------------------------------------------------------------------
        */

        $subCategoryResult =
            $this->resolveSubCategory(
                $companyId,
                (int) $category->id,
                (string) $validated['subcategory']
            );

        if (!$subCategoryResult['found']) {
            $canCreate =
                $this->guard->can(
                    $user,
                    'subcategory.create'
                );

            return [
                'success' =>
                    false,

                'type' =>
                    'subcategory_not_found',

                'message' =>
                    'Subcategory "'
                    . $validated['subcategory']
                    . '" belum ditemukan '
                    . 'di category "'
                    . $category->category_name
                    . '".',

                'subcategory' =>
                    $validated['subcategory'],

                'category_id' =>
                    $category->id,

                'category_name' =>
                    $category->category_name,

                'can_create' =>
                    $canCreate,

                'required_permission' =>
                    'subcategory.create',
            ];
        }

        $subCategory =
            $subCategoryResult['model'];

        /*
        |--------------------------------------------------------------------------
        | VENDOR
        |--------------------------------------------------------------------------
        */

        $vendorResult =
            $this->resolveVendor(
                $companyId,
                (string) $validated['vendor']
            );

        if (!$vendorResult['found']) {
            $canCreate =
                $this->guard->can(
                    $user,
                    'vendor.create'
                );

            return [
                'success' =>
                    false,

                'type' =>
                    'vendor_not_found',

                'message' =>
                    'Vendor "'
                    . $validated['vendor']
                    . '" belum ditemukan.',

                'vendor' =>
                    $validated['vendor'],

                'can_create' =>
                    $canCreate,

                'required_permission' =>
                    'vendor.create',
            ];
        }

        $vendor =
            $vendorResult['model'];

        /*
        |--------------------------------------------------------------------------
        | PRICE
        |--------------------------------------------------------------------------
        */

        $purchasePrice =
            $this->normalizePrice(
                $validated['purchase_price']
            );

        if ($purchasePrice < 0) {
            return [
                'success' =>
                    false,

                'type' =>
                    'invalid_price',

                'message' =>
                    'Harga pembelian tidak valid.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | OPTIONAL DATA
        |--------------------------------------------------------------------------
        */

        $optionalFields = [
            'brand',
            'model',
            'serial_number',
            'description',
            'purchase_date',
            'asset_condition',
            'responsible_user_id',
            'location',
            'status',
            'depreciation_method',
            'useful_life',
            'residual_value',
            'depreciation_start_date',
            'warranty_start',
            'warranty_end',
            'warranty_note',
            'maintenance_required',
            'maintenance_type',
            'maintenance_trigger',
            'maintenance_interval',
            'maintenance_interval_unit',
            'maintenance_start_date',
            'last_maintenance_date',
            'next_maintenance_date',
        ];

        $optional =
            [];

        foreach (
            $optionalFields as $field
        ) {
            if (
                array_key_exists(
                    $field,
                    $validated
                )
            ) {
                $optional[$field] =
                    $validated[$field];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FINAL PREVIEW DATA
        |--------------------------------------------------------------------------
        */

        $previewData = [
            'asset_name' =>
                strtoupper(
                    trim(
                        (string)
                        $validated['asset_name']
                    )
                ),

            'category_id' =>
                (int) $category->id,

            'category_name' =>
                $category->category_name,

            'subcategory_id' =>
                (int) $subCategory->id,

            'subcategory_name' =>
                $subCategory->sub_category_name,

            'vendor_id' =>
                (int) $vendor->id,

            'vendor_name' =>
                $vendor->vendor_name,

            'purchase_price' =>
                $purchasePrice,

            'purchase_price_formatted' =>
                'Rp '
                . number_format(
                    $purchasePrice,
                    0,
                    ',',
                    '.'
                ),

            'optional' =>
                $optional,
        ];

        /*
        |--------------------------------------------------------------------------
        | CONFIRMATION REQUIRED
        |--------------------------------------------------------------------------
        */

        return [
            'success' =>
                true,

            'type' =>
                'confirmation_required',

            'action' =>
                'asset.create',

            'message' =>
                'Data asset sudah lengkap '
                . 'dan siap dibuat. '
                . 'Silakan konfirmasi.',

            'data' =>
                $previewData,
        ];
    }

    /**
     * Resolve category.
     */
    protected function resolveCategory(
        int $companyId,
        string $name
    ): array {
        $name =
            trim($name);

        $category =
            Category::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'status',
                    1
                )
                ->whereRaw(
                    'LOWER(category_name) = ?',
                    [
                        strtolower($name),
                    ]
                )
                ->first();

        return [
            'found' =>
                $category !== null,

            'model' =>
                $category,
        ];
    }

    /**
     * Resolve subcategory.
     */
    protected function resolveSubCategory(
        int $companyId,
        int $categoryId,
        string $name
    ): array {
        $name =
            trim($name);

        $subCategory =
            SubCategory::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'category_id',
                    $categoryId
                )
                ->where(
                    'status',
                    1
                )
                ->whereRaw(
                    'LOWER(sub_category_name) = ?',
                    [
                        strtolower($name),
                    ]
                )
                ->first();

        return [
            'found' =>
                $subCategory !== null,

            'model' =>
                $subCategory,
        ];
    }

    /**
     * Resolve vendor.
     */
    protected function resolveVendor(
        int $companyId,
        string $name
    ): array {
        $name =
            trim($name);

        $vendor =
            Vendor::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'status',
                    1
                )
                ->whereRaw(
                    'LOWER(vendor_name) = ?',
                    [
                        strtolower($name),
                    ]
                )
                ->first();

        return [
            'found' =>
                $vendor !== null,

            'model' =>
                $vendor,
        ];
    }

    /**
     * Normalize Indonesian price.
     */
    protected function normalizePrice(
        mixed $value
    ): float {
        /*
        |--------------------------------------------------------------------------
        | Numeric
        |--------------------------------------------------------------------------
        */

        if (
            is_int($value) ||
            is_float($value)
        ) {
            return (float) $value;
        }

        if (
            is_numeric($value)
        ) {
            return (float) $value;
        }

        /*
        |--------------------------------------------------------------------------
        | String
        |--------------------------------------------------------------------------
        */

        $value =
            strtolower(
                trim(
                    (string) $value
                )
            );

        $value =
            str_replace(
                [
                    'rp',
                    ' ',
                ],
                '',
                $value
            );

        /*
        |--------------------------------------------------------------------------
        | JUTA
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $value,
                'juta'
            )
        ) {
            $number =
                str_replace(
                    'juta',
                    '',
                    $value
                );

            $number =
                str_replace(
                    '.',
                    '',
                    $number
                );

            return
                (float) $number
                * 1_000_000;
        }

        /*
        |--------------------------------------------------------------------------
        | JT
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $value,
                'jt'
            )
        ) {
            $number =
                str_replace(
                    'jt',
                    '',
                    $value
                );

            $number =
                str_replace(
                    '.',
                    '',
                    $number
                );

            return
                (float) $number
                * 1_000_000;
        }

        /*
        |--------------------------------------------------------------------------
        | RIBU
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $value,
                'ribu'
            )
        ) {
            $number =
                str_replace(
                    'ribu',
                    '',
                    $value
                );

            $number =
                str_replace(
                    '.',
                    '',
                    $number
                );

            return
                (float) $number
                * 1_000;
        }

        /*
        |--------------------------------------------------------------------------
        | RB
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $value,
                'rb'
            )
        ) {
            $number =
                str_replace(
                    'rb',
                    '',
                    $value
                );

            $number =
                str_replace(
                    '.',
                    '',
                    $number
                );

            return
                (float) $number
                * 1_000;
        }

        /*
        |--------------------------------------------------------------------------
        | NORMAL INDONESIAN NUMBER
        |--------------------------------------------------------------------------
        */

        $value =
            preg_replace(
                '/[^0-9.,]/',
                '',
                $value
            );

        if (
            $value === null ||
            $value === ''
        ) {
            return 0;
        }

        /*
        |--------------------------------------------------------------------------
        | MULTIPLE DOTS
        |--------------------------------------------------------------------------
        */

        if (
            substr_count(
                $value,
                '.'
            ) > 1
        ) {
            $value =
                str_replace(
                    '.',
                    '',
                    $value
                );
        }

        /*
        |--------------------------------------------------------------------------
        | COMMA DECIMAL
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $value,
                ','
            )
        ) {
            $value =
                str_replace(
                    '.',
                    '',
                    $value
                );

            $value =
                str_replace(
                    ',',
                    '.',
                    $value
                );
        }

        return
            (float) $value;
    }
}