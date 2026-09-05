<?php

namespace App\Imports;

use App\Helpers\CodeHelper;
use App\Models\Asset;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AssetImport implements
    ToModel,
    WithHeadingRow,
    WithChunkReading,
    WithBatchInserts
{
    /*
    |--------------------------------------------------------------------------
    | COMPANY
    |--------------------------------------------------------------------------
    */

    protected int $companyId;

    /*
    |--------------------------------------------------------------------------
    | CACHE
    |--------------------------------------------------------------------------
    |
    | Cache digunakan supaya data yang sama tidak terus-menerus
    | melakukan query ke database.
    |
    */

    protected array $categoryCache = [];

    protected array $subCategoryCache = [];

    protected array $vendorCache = [];

    protected array $userCache = [];

    /*
    |--------------------------------------------------------------------------
    | ASSET CODE GENERATOR
    |--------------------------------------------------------------------------
    |
    | Counter hanya mengambil nomor terakhir dari database sekali.
    | Setelah itu nomor dinaikkan di memory.
    |
    */

    protected int $assetCodeCounter = 0;

    protected bool $assetCodeInitialized = false;

    /*
    |--------------------------------------------------------------------------
    | CONSTRUCTOR
    |--------------------------------------------------------------------------
    */

    public function __construct()
    {
        $user = Auth::user();

        if (!$user || !$user->company_id) {
            throw new \Exception(
                'Company user tidak ditemukan.'
            );
        }

        $this->companyId = (int) $user->company_id;
    }

    /*
    |--------------------------------------------------------------------------
    | MODEL
    |--------------------------------------------------------------------------
    */

    public function model(array $row): Model|array|null
    {
        /*
        |--------------------------------------------------------------------------
        | NORMALISASI ROW
        |--------------------------------------------------------------------------
        |
        | Heading Excel dari WithHeadingRow biasanya sudah menjadi lowercase
        | dan underscore. Kita tetap trim value penting supaya lebih bersih.
        |
        */

        $assetName = trim(
            (string) ($row['asset_name'] ?? '')
        );

        $categoryName = trim(
            (string) ($row['category'] ?? '')
        );

        /*
        |--------------------------------------------------------------------------
        | SKIP BARIS KOSONG
        |--------------------------------------------------------------------------
        */

        if (
            $assetName === '' &&
            $categoryName === ''
        ) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | ASSET NAME
        |--------------------------------------------------------------------------
        */

        if ($assetName === '') {
            throw new \Exception(
                'Asset Name wajib diisi.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CATEGORY
        |--------------------------------------------------------------------------
        */

        $category = $this->resolveCategory(
            $categoryName
        );

        /*
        |--------------------------------------------------------------------------
        | SUB CATEGORY
        |--------------------------------------------------------------------------
        */

        $subCategory = $this->resolveSubCategory(
            $category,
            $row['sub_category'] ?? null
        );

        /*
        |--------------------------------------------------------------------------
        | VENDOR
        |--------------------------------------------------------------------------
        */

        $vendor = $this->resolveVendor(
            $row['vendor'] ?? null,
            $row['vendor_address'] ?? null
        );

        /*
        |--------------------------------------------------------------------------
        | RESPONSIBLE USER
        |--------------------------------------------------------------------------
        */

        $responsibleUser = $this->resolveResponsibleUser(
            $row['responsible_user'] ?? null
        );

        /*
        |--------------------------------------------------------------------------
        | DATE HELPER
        |--------------------------------------------------------------------------
        */

        $parseDate = function ($value) use ($assetName) {

            if (
                $value === null ||
                trim((string) $value) === ''
            ) {
                return null;
            }

            try {

                /*
                |--------------------------------------------------------------------------
                | Excel Serial Date
                |--------------------------------------------------------------------------
                */

                if (is_numeric($value)) {

                    return Carbon::createFromTimestamp(
                        \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp(
                            $value
                        )
                    )->format('Y-m-d');
                }

                /*
                |--------------------------------------------------------------------------
                | Normal Date
                |--------------------------------------------------------------------------
                */

                return Carbon::parse($value)
                    ->format('Y-m-d');

            } catch (\Throwable $e) {

                throw new \Exception(
                    'Format tanggal tidak valid: ' .
                    $value .
                    ' pada asset "' .
                    $assetName .
                    '".'
                );
            }
        };

        /*
        |--------------------------------------------------------------------------
        | MAINTENANCE
        |--------------------------------------------------------------------------
        */

        $maintenanceRequired = strtolower(
            trim(
                (string) (
                    $row['maintenance_required'] ?? 'no'
                )
            )
        ) === 'yes';

        $maintenanceType = null;

        $maintenanceTrigger = null;

        $maintenanceInterval = null;

        $maintenanceIntervalUnit = null;

        $maintenanceStartDate = null;

        if ($maintenanceRequired) {

            $maintenanceType = $this->cleanString(
                $row['maintenance_type'] ?? null
            );

            $maintenanceTrigger = $this->cleanString(
                $row['maintenance_trigger'] ?? null
            );

            $maintenanceInterval = $this->cleanNumber(
                $row['maintenance_interval'] ?? null
            );

            $maintenanceIntervalUnit = $this->cleanString(
                $row['maintenance_interval_unit'] ?? null
            );

            $maintenanceStartDate = $parseDate(
                $row['maintenance_start_date'] ?? null
            );
        }

        /*
        |--------------------------------------------------------------------------
        | NEXT MAINTENANCE DATE
        |--------------------------------------------------------------------------
        */

        $nextMaintenanceDate = $this->calculateNextMaintenanceDate(
            $maintenanceRequired,
            $maintenanceStartDate,
            $maintenanceInterval,
            $maintenanceIntervalUnit
        );

        /*
        |--------------------------------------------------------------------------
        | ASSET CODE
        |--------------------------------------------------------------------------
        |
        | Asset Code dari Excel sengaja tidak digunakan.
        | Sistem selalu generate otomatis.
        |
        */

        $assetCode = $this->generateAssetCode();

        /*
        |--------------------------------------------------------------------------
        | RETURN ASSET
        |--------------------------------------------------------------------------
        */

        return new Asset([

            /*
            |--------------------------------------------------------------------------
            | BASIC
            |--------------------------------------------------------------------------
            */

            'company_id' => $this->companyId,

            'category_id' => $category->id,

            'sub_category_id' =>
                $subCategory?->id,

            'vendor_id' =>
                $vendor?->id,

            'responsible_user_id' =>
                $responsibleUser?->id,

            'asset_code' =>
                $assetCode,

            'asset_name' =>
                $assetName,

            'description' =>
                $this->cleanString(
                    $row['description'] ?? null
                ),

            /*
            |--------------------------------------------------------------------------
            | ASSET DETAIL
            |--------------------------------------------------------------------------
            */

            'asset_condition' =>
                strtolower(
                    trim(
                        (string) (
                            $row['condition'] ?? 'new'
                        )
                    )
                ),

            'brand' =>
                $this->cleanString(
                    $row['brand'] ?? null
                ),

            'model' =>
                $this->cleanString(
                    $row['model'] ?? null
                ),

            'serial_number' =>
                $this->cleanString(
                    $row['serial_number'] ?? null
                ),

            /*
            |--------------------------------------------------------------------------
            | PURCHASE
            |--------------------------------------------------------------------------
            */

            'purchase_date' =>
                $parseDate(
                    $row['purchase_date'] ?? null
                ),

            'purchase_price' =>
                $this->cleanNumber(
                    $row['purchase_price'] ?? null
                ),

            'purchase_invoice' =>
                $this->cleanString(
                    $row['purchase_invoice'] ?? null
                ),

            /*
            |--------------------------------------------------------------------------
            | DEPRECIATION
            |--------------------------------------------------------------------------
            */

            'depreciation_method' =>
                $this->cleanString(
                    $row['depreciation_method']
                    ?? 'straight_line'
                ),

            'useful_life' =>
                $this->cleanNumber(
                    $row['useful_life'] ?? null
                ),

            'residual_value' =>
                $this->cleanNumber(
                    $row['residual_value'] ?? null
                ),

            'depreciation_start_date' =>
                $parseDate(
                    $row['depreciation_start_date'] ?? null
                ),

            /*
            |--------------------------------------------------------------------------
            | WARRANTY
            |--------------------------------------------------------------------------
            */

            'warranty_start' =>
                $parseDate(
                    $row['warranty_start'] ?? null
                ),

            'warranty_end' =>
                $parseDate(
                    $row['warranty_end'] ?? null
                ),

            'warranty_note' =>
                $this->cleanString(
                    $row['warranty_note'] ?? null
                ),

            /*
            |--------------------------------------------------------------------------
            | MAINTENANCE
            |--------------------------------------------------------------------------
            */

            'maintenance_required' =>
                $maintenanceRequired ? 1 : 0,

            'maintenance_type' =>
                $maintenanceType,

            'maintenance_trigger' =>
                $maintenanceTrigger,

            'maintenance_interval' =>
                $maintenanceInterval,

            'maintenance_interval_unit' =>
                $maintenanceIntervalUnit,

            'maintenance_start_date' =>
                $maintenanceStartDate,

            'last_maintenance_date' =>
                null,

            'next_maintenance_date' =>
                $nextMaintenanceDate,

            /*
            |--------------------------------------------------------------------------
            | LOCATION
            |--------------------------------------------------------------------------
            */

            'location' =>
                $this->cleanString(
                    $row['location'] ?? null
                ),

            /*
            |--------------------------------------------------------------------------
            | SYSTEM
            |--------------------------------------------------------------------------
            */

            'status' => 1,

            'qr_token' =>
                Str::uuid()->toString(),

            'qr_generated_at' =>
                now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVE CATEGORY
    |--------------------------------------------------------------------------
    */

    protected function resolveCategory(
        string $categoryName
    ): Category {

        $categoryName = trim($categoryName);

        if ($categoryName === '') {
            throw new \Exception(
                'Category wajib diisi.'
            );
        }

        $categoryKey = strtolower(
            $categoryName
        );

        /*
        |--------------------------------------------------------------------------
        | CACHE
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $this->categoryCache[$categoryKey]
            )
        ) {
            return $this->categoryCache[
                $categoryKey
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | FIND EXISTING
        |--------------------------------------------------------------------------
        */

        $category = Category::where(
                'company_id',
                $this->companyId
            )
            ->whereRaw(
                'LOWER(TRIM(category_name)) = ?',
                [$categoryKey]
            )
            ->first();

        /*
        |--------------------------------------------------------------------------
        | CREATE IF NOT FOUND
        |--------------------------------------------------------------------------
        */

        if (!$category) {

            $category = Category::create([

                'company_id' =>
                    $this->companyId,

                'category_code' =>
                    CodeHelper::generateNumber(
                        'CAT-',
                        Category::class,
                        'category_code',
                        $this->companyId
                    ),

                'category_name' =>
                    strtoupper($categoryName),

                'description' =>
                    null,

                'status' =>
                    1,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CACHE
        |--------------------------------------------------------------------------
        */

        $this->categoryCache[
            $categoryKey
        ] = $category;

        return $category;
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVE SUB CATEGORY
    |--------------------------------------------------------------------------
    */

    protected function resolveSubCategory(
        Category $category,
        $subCategoryName
    ): ?SubCategory {

        $subCategoryName = trim(
            (string) $subCategoryName
        );

        /*
        |--------------------------------------------------------------------------
        | OPTIONAL
        |--------------------------------------------------------------------------
        */

        if ($subCategoryName === '') {
            return null;
        }

        $subCategoryKey =
            $category->id .
            '|' .
            strtolower($subCategoryName);

        /*
        |--------------------------------------------------------------------------
        | CACHE
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $this->subCategoryCache[
                    $subCategoryKey
                ]
            )
        ) {
            return $this->subCategoryCache[
                $subCategoryKey
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | FIND EXISTING
        |--------------------------------------------------------------------------
        */

        $subCategory = SubCategory::where(
                'company_id',
                $this->companyId
            )
            ->where(
                'category_id',
                $category->id
            )
            ->whereRaw(
                'LOWER(TRIM(sub_category_name)) = ?',
                [strtolower($subCategoryName)]
            )
            ->first();

        /*
        |--------------------------------------------------------------------------
        | CREATE IF NOT FOUND
        |--------------------------------------------------------------------------
        */

        if (!$subCategory) {

            $subCategory = SubCategory::create([

                'company_id' =>
                    $this->companyId,

                'category_id' =>
                    $category->id,

                'sub_category_code' =>
                    CodeHelper::generateNumber(
                        'SUBCAT-',
                        SubCategory::class,
                        'sub_category_code',
                        $this->companyId
                    ),

                'sub_category_name' =>
                    strtoupper($subCategoryName),

                'description' =>
                    null,

                'status' =>
                    1,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CACHE
        |--------------------------------------------------------------------------
        */

        $this->subCategoryCache[
            $subCategoryKey
        ] = $subCategory;

        return $subCategory;
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVE VENDOR
    |--------------------------------------------------------------------------
    |
    | Vendor dianggap sama apabila:
    |
    | company_id
    | + vendor_name
    | + address
    |
    | sama.
    |
    */

    protected function resolveVendor(
        $vendorName,
        $vendorAddress
    ): ?Vendor {

        $vendorName = trim(
            (string) $vendorName
        );

        $vendorAddress = trim(
            (string) $vendorAddress
        );

        /*
        |--------------------------------------------------------------------------
        | OPTIONAL
        |--------------------------------------------------------------------------
        */

        if ($vendorName === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | CACHE KEY
        |--------------------------------------------------------------------------
        */

        $vendorKey =
            strtolower($vendorName) .
            '|' .
            strtolower($vendorAddress);

        /*
        |--------------------------------------------------------------------------
        | CACHE
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $this->vendorCache[$vendorKey]
            )
        ) {
            return $this->vendorCache[
                $vendorKey
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | FIND EXISTING VENDOR
        |--------------------------------------------------------------------------
        */

        $vendorQuery = Vendor::where(
                'company_id',
                $this->companyId
            )
            ->whereRaw(
                'LOWER(TRIM(vendor_name)) = ?',
                [strtolower($vendorName)]
            );

        /*
        |--------------------------------------------------------------------------
        | ADDRESS MATCHING
        |--------------------------------------------------------------------------
        */

        if ($vendorAddress !== '') {

            $vendorQuery->whereRaw(
                'LOWER(TRIM(COALESCE(address, ""))) = ?',
                [strtolower($vendorAddress)]
            );

        } else {

            $vendorQuery->where(function ($query) {

                $query
                    ->whereNull('address')
                    ->orWhereRaw(
                        'TRIM(address) = ?',
                        ['']
                    );

            });
        }

        $vendor = $vendorQuery->first();

        /*
        |--------------------------------------------------------------------------
        | CREATE VENDOR
        |--------------------------------------------------------------------------
        */

        if (!$vendor) {

            $vendor = Vendor::create([

                'company_id' =>
                    $this->companyId,

                'vendor_code' =>
                    CodeHelper::generateNumber(
                        'VD-',
                        Vendor::class,
                        'vendor_code',
                        $this->companyId
                    ),

                'vendor_name' =>
                    $vendorName,

                'address' =>
                    $vendorAddress !== ''
                        ? $vendorAddress
                        : null,

                /*
                |--------------------------------------------------------------------------
                | Email belum tersedia dari template Excel.
                |--------------------------------------------------------------------------
                */

                'email' =>
                    null,

                'status' =>
                    1,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CACHE
        |--------------------------------------------------------------------------
        */

        $this->vendorCache[
            $vendorKey
        ] = $vendor;

        return $vendor;
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVE RESPONSIBLE USER
    |--------------------------------------------------------------------------
    |
    | Responsible User boleh kosong.
    |
    | Jika diisi tetapi user tidak ditemukan,
    | import akan dihentikan dengan error.
    |
    */

    protected function resolveResponsibleUser(
        $responsibleName
    ): ?User {

        $responsibleName = trim(
            (string) $responsibleName
        );

        /*
        |--------------------------------------------------------------------------
        | OPTIONAL
        |--------------------------------------------------------------------------
        */

        if ($responsibleName === '') {
            return null;
        }

        $userKey = strtolower(
            $responsibleName
        );

        /*
        |--------------------------------------------------------------------------
        | CACHE
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $this->userCache[$userKey]
            )
        ) {
            return $this->userCache[
                $userKey
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | FIND USER
        |--------------------------------------------------------------------------
        */

        $user = User::where(
                'company_id',
                $this->companyId
            )
            ->whereRaw(
                'LOWER(TRIM(name)) = ?',
                [strtolower($responsibleName)]
            )
            ->first();

        /*
        |--------------------------------------------------------------------------
        | NOT FOUND
        |--------------------------------------------------------------------------
        */

        if (!$user) {

            throw new \Exception(
                'Responsible User "' .
                $responsibleName .
                '" tidak ditemukan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CACHE
        |--------------------------------------------------------------------------
        */

        $this->userCache[
            $userKey
        ] = $user;

        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULATE NEXT MAINTENANCE
    |--------------------------------------------------------------------------
    */

    protected function calculateNextMaintenanceDate(
        bool $maintenanceRequired,
        ?string $startDate,
        $interval,
        $intervalUnit
    ): ?string {

        if (
            !$maintenanceRequired ||
            !$startDate ||
            !$interval ||
            !$intervalUnit
        ) {
            return null;
        }

        $interval = (int) $interval;

        if ($interval <= 0) {
            return null;
        }

        $unit = strtolower(
            trim((string) $intervalUnit)
        );

        $date = Carbon::parse(
            $startDate
        );

        switch ($unit) {

            case 'day':
            case 'days':

                $date->addDays(
                    $interval
                );

                break;

            case 'month':
            case 'months':

                $date->addMonths(
                    $interval
                );

                break;

            case 'year':
            case 'years':

                $date->addYears(
                    $interval
                );

                break;

            default:

                return null;
        }

        return $date->format('Y-m-d');
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE ASSET CODE
    |--------------------------------------------------------------------------
    |
    | Sebelumnya setiap asset melakukan query EXISTS.
    |
    | Sekarang:
    |
    | 1. Ambil nomor terbesar sekali.
    | 2. Counter berjalan di memory.
    |
    | Contoh:
    |
    | DB terakhir AST-15
    | Import berikutnya:
    | AST-16
    | AST-17
    | AST-18
    |
    */

    protected function generateAssetCode(): string
    {
        /*
        |--------------------------------------------------------------------------
        | INITIALIZE ONCE
        |--------------------------------------------------------------------------
        */

        if (!$this->assetCodeInitialized) {

            $lastNumber = Asset::withTrashed()
                ->where(
                    'company_id',
                    $this->companyId
                )
                ->where(
                    'asset_code',
                    'LIKE',
                    'AST-%'
                )
                ->selectRaw("
                    MAX(
                        CAST(
                            SUBSTRING(asset_code, 5)
                            AS UNSIGNED
                        )
                    ) AS max_number
                ")
                ->value('max_number');

            $this->assetCodeCounter =
                $lastNumber !== null
                    ? ((int) $lastNumber + 1)
                    : 0;

            $this->assetCodeInitialized = true;
        }

        /*
        |--------------------------------------------------------------------------
        | GENERATE
        |--------------------------------------------------------------------------
        */

        $code = 'AST-' . str_pad(
            $this->assetCodeCounter,
            2,
            '0',
            STR_PAD_LEFT
        );

        $this->assetCodeCounter++;

        return $code;
    }

    /*
    |--------------------------------------------------------------------------
    | CLEAN STRING
    |--------------------------------------------------------------------------
    */

    protected function cleanString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(
            (string) $value
        );

        return $value === ''
            ? null
            : $value;
    }

    /*
    |--------------------------------------------------------------------------
    | CLEAN NUMBER
    |--------------------------------------------------------------------------
    |
    | Mendukung:
    |
    | 12500000
    | 12.500.000
    | 12,500,000
    | Rp 12.500.000
    |
    */

    protected function cleanNumber($value)
    {
        if (
            $value === null ||
            trim((string) $value) === ''
        ) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Numeric dari Excel
        |--------------------------------------------------------------------------
        */

        if (
            is_int($value) ||
            is_float($value) ||
            is_numeric($value)
        ) {
            return $value;
        }

        $value = trim(
            (string) $value
        );

        /*
        |--------------------------------------------------------------------------
        | Remove currency / spaces
        |--------------------------------------------------------------------------
        */

        $value = preg_replace(
            '/[^\d,.\-]/',
            '',
            $value
        );

        if ($value === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Format Indonesia
        |
        | 12.500.000
        |--------------------------------------------------------------------------
        */

        if (
            substr_count($value, '.') > 1 &&
            strpos($value, ',') === false
        ) {
            $value = str_replace(
                '.',
                '',
                $value
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Format:
        |
        | 12.500,50
        |--------------------------------------------------------------------------
        */

        elseif (
            strpos($value, '.') !== false &&
            strpos($value, ',') !== false
        ) {
            $value = str_replace(
                '.',
                '',
                $value
            );

            $value = str_replace(
                ',',
                '.',
                $value
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Format:
        |
        | 12,500
        |--------------------------------------------------------------------------
        |
        | Karena field asset biasanya menggunakan angka rupiah,
        | koma dianggap sebagai pemisah ribuan apabila tidak ada
        | titik desimal.
        |
        */

        elseif (
            strpos($value, ',') !== false
        ) {
            $value = str_replace(
                ',',
                '',
                $value
            );
        }

        return is_numeric($value)
            ? (float) $value
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | CHUNK SIZE
    |--------------------------------------------------------------------------
    |
    | Cocok untuk file besar.
    |
    */

    public function chunkSize(): int
    {
        return 1000;
    }

    /*
    |--------------------------------------------------------------------------
    | BATCH SIZE
    |--------------------------------------------------------------------------
    |
    | Insert dilakukan per 1000 record.
    |
    */

    public function batchSize(): int
    {
        return 1000;
    }
}
