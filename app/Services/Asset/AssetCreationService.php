<?php

namespace App\Services\Asset;

use App\Helpers\CodeHelper;
use App\Helpers\PlanLimitHelper;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AssetCreationService
{
    /**
     * Create asset from normalized data.
     *
     * IMPORTANT:
     * - company_id selalu berasal dari authenticated user
     * - asset_code selalu dibuat backend
     * - AI tidak boleh menentukan database ID lintas company
     */
    public function create(
        User $user,
        array $data
    ): Asset {
        $companyId = (int) $user->company_id;

        /*
        |--------------------------------------------------------------------------
        | Permission / business checks
        |--------------------------------------------------------------------------
        |
        | Permission utama sebaiknya sudah diperiksa sebelum service dipanggil.
        | Kita tetap menjaga limit di sini karena service ini merupakan
        | boundary terakhir sebelum INSERT.
        |
        */

        if (!PlanLimitHelper::canAddAssets(1)) {
            throw new RuntimeException(
                'Batas asset pada subscription Anda sudah tercapai.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Required fields
        |--------------------------------------------------------------------------
        */

        foreach ([
            'asset_name',
            'category_id',
            'vendor_id',
            'purchase_price',
        ] as $field) {
            if (
                !array_key_exists($field, $data) ||
                $data[$field] === null ||
                $data[$field] === ''
            ) {
                throw new RuntimeException(
                    "Field {$field} wajib diisi."
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CATEGORY
        |--------------------------------------------------------------------------
        */

        $category = Category::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->whereKey($data['category_id'])
            ->first();

        if (!$category) {
            throw new RuntimeException(
                'Category tidak valid.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SUBCATEGORY
        |--------------------------------------------------------------------------
        */

        $subCategory = null;

        if (
            isset($data['sub_category_id']) &&
            $data['sub_category_id'] !== null
        ) {
            $subCategory = SubCategory::query()
                ->where('company_id', $companyId)
                ->where('category_id', $category->id)
                ->where('status', 1)
                ->whereKey($data['sub_category_id'])
                ->first();

            if (!$subCategory) {
                throw new RuntimeException(
                    'Subcategory tidak valid.'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | VENDOR
        |--------------------------------------------------------------------------
        */

        $vendor = Vendor::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->whereKey($data['vendor_id'])
            ->first();

        if (!$vendor) {
            throw new RuntimeException(
                'Vendor tidak valid.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIBLE USER
        |--------------------------------------------------------------------------
        */

        $responsibleUserId = null;

        if (
            isset($data['responsible_user_id']) &&
            $data['responsible_user_id'] !== null
        ) {
            $responsibleUser = User::query()
                ->where('company_id', $companyId)
                ->where('status', 1)
                ->whereKey($data['responsible_user_id'])
                ->first();

            if (!$responsibleUser) {
                throw new RuntimeException(
                    'Responsible user tidak valid.'
                );
            }

            $responsibleUserId = $responsibleUser->id;
        }

        /*
        |--------------------------------------------------------------------------
        | MAINTENANCE
        |--------------------------------------------------------------------------
        */

        $maintenanceRequired = (bool) (
            $data['maintenance_required'] ?? false
        );

        $nextMaintenanceDate = null;

        if ($maintenanceRequired) {
            $nextMaintenanceDate =
                $data['next_maintenance_date'] ?? null;

            if (!$nextMaintenanceDate) {
                throw new RuntimeException(
                    'Tanggal maintenance berikutnya wajib diisi '
                    . 'jika maintenance aktif.'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TRANSACTION
        |--------------------------------------------------------------------------
        */

        return DB::transaction(function () use (
            $user,
            $companyId,
            $data,
            $category,
            $subCategory,
            $vendor,
            $responsibleUserId,
            $maintenanceRequired,
            $nextMaintenanceDate
        ) {

            /*
            |--------------------------------------------------------------------------
            | Generate Asset Code
            |--------------------------------------------------------------------------
            */

            $assetCode = CodeHelper::generateNumber(
                'AST-',
                Asset::class,
                'asset_code',
                $companyId
            );

            /*
            |--------------------------------------------------------------------------
            | QR Token
            |--------------------------------------------------------------------------
            */

            $qrToken = Str::uuid()->toString();

            /*
            |--------------------------------------------------------------------------
            | Create Asset
            |--------------------------------------------------------------------------
            */

            $asset = Asset::create([
                'company_id' =>
                    $companyId,

                'category_id' =>
                    $category->id,

                'sub_category_id' =>
                    $subCategory?->id,

                'vendor_id' =>
                    $vendor->id,

                'responsible_user_id' =>
                    $responsibleUserId,

                /*
                 * AI TIDAK BOLEH menentukan ini.
                 */
                'asset_code' =>
                    $assetCode,

                'asset_name' =>
                    strtoupper(
                        trim(
                            $data['asset_name']
                        )
                    ),

                'asset_condition' =>
                    $data['asset_condition']
                    ?? 'new',

                'brand' =>
                    $data['brand'] ?? null,

                'model' =>
                    $data['model'] ?? null,

                'serial_number' =>
                    $data['serial_number'] ?? null,

                'description' =>
                    $data['description'] ?? null,

                'purchase_date' =>
                    $data['purchase_date'] ?? null,

                'purchase_price' =>
                    $data['purchase_price'],

                'purchase_invoice' =>
                    $data['purchase_invoice'] ?? null,

                'depreciation_method' =>
                    $data['depreciation_method']
                    ?? null,

                'useful_life' =>
                    $data['useful_life'] ?? null,

                'residual_value' =>
                    $data['residual_value'] ?? 0,

                'depreciation_start_date' =>
                    $data['depreciation_start_date']
                    ?? null,

                'warranty_start' =>
                    $data['warranty_start'] ?? null,

                'warranty_end' =>
                    $data['warranty_end'] ?? null,

                'warranty_note' =>
                    $data['warranty_note'] ?? null,

                'maintenance_required' =>
                    $maintenanceRequired,

                'maintenance_type' =>
                    $maintenanceRequired
                        ? ($data['maintenance_type'] ?? null)
                        : null,

                'maintenance_trigger' =>
                    $maintenanceRequired
                        ? ($data['maintenance_trigger'] ?? null)
                        : null,

                'maintenance_interval' =>
                    $maintenanceRequired
                        ? ($data['maintenance_interval'] ?? null)
                        : null,

                'maintenance_interval_unit' =>
                    $maintenanceRequired
                        ? ($data['maintenance_interval_unit'] ?? null)
                        : null,

                'maintenance_start_date' =>
                    $maintenanceRequired
                        ? ($data['maintenance_start_date'] ?? null)
                        : null,

                'last_maintenance_date' =>
                    $maintenanceRequired
                        ? ($data['last_maintenance_date'] ?? null)
                        : null,

                'next_maintenance_date' =>
                    $nextMaintenanceDate,

                'location' =>
                    $data['location'] ?? null,

                'status' =>
                    $data['status'] ?? 1,

                'qr_token' =>
                    $qrToken,

                'qr_generated_at' =>
                    now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Initial Assignment
            |--------------------------------------------------------------------------
            */

            if ($responsibleUserId) {
                AssetAssignment::create([
                    'asset_id' =>
                        $asset->id,

                    'user_id' =>
                        $responsibleUserId,

                    'start_at' =>
                        now(),

                    'end_at' =>
                        null,

                    'assignment_type' =>
                        'initial',

                    'reason' =>
                        'Initial assignment via Vasetra',

                    'notes' =>
                        null,

                    'created_by' =>
                        $user->id,
                ]);
            }

            return $asset->fresh([
                'category',
                'subCategory',
                'vendor',
                'responsibleUser',
            ]);
        });
    }
}