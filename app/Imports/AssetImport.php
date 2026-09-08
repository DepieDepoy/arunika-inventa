<?php

namespace App\Imports;

use App\Helpers\CodeHelper;
use App\Models\Asset;
use App\Models\Category;
use App\Models\ImportHistory;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AssetImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading
{
    /*
    |--------------------------------------------------------------------------
    | COMPANY
    |--------------------------------------------------------------------------
    */

    protected int $companyId;

    /*
    |--------------------------------------------------------------------------
    | IMPORT HISTORY
    |--------------------------------------------------------------------------
    */

    protected ImportHistory $history;

    /*
    |--------------------------------------------------------------------------
    | CACHE
    |--------------------------------------------------------------------------
    */

    protected array $categoryCache = [];

    protected array $subCategoryCache = [];

    protected array $vendorCache = [];

    protected array $userCache = [];

    protected bool $userCacheInitialized = false;

    /*
    |--------------------------------------------------------------------------
    | SERIAL NUMBER
    |--------------------------------------------------------------------------
    */

    protected array $importSerialNumbers = [];

    /*
    |--------------------------------------------------------------------------
    | ASSET CODE
    |--------------------------------------------------------------------------
    */

    protected int $assetCodeCounter = 0;

    protected bool $assetCodeInitialized = false;

    /*
    |--------------------------------------------------------------------------
    | IMPORT ROW NUMBER
    |--------------------------------------------------------------------------
    |
    | Nomor logical row data import.
    |
    | Data pertama = 1
    | Data kedua   = 2
    | dst.
    |
    | Ini sengaja tidak menggunakan nomor fisik Excel
    | karena WithHeadingRow membuat header berada di row Excel pertama.
    |
    */

    protected int $importRowNumber = 1;

    /*
    |--------------------------------------------------------------------------
    | CONSTRUCTOR
    |--------------------------------------------------------------------------
    */

    public function __construct(
        ImportHistory $history
    ) {
        $this->history = $history;

        $this->companyId = (int) $history->company_id;

        $this->initializeUserCache();
    }

    /*
    |--------------------------------------------------------------------------
    | COLLECTION
    |--------------------------------------------------------------------------
    */

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | PREPARE
        |--------------------------------------------------------------------------
        */

        $preparedRows = [];

        foreach ($rows as $row) {

            $rowArray = $row->toArray();

            $assetName = trim(
                (string) (
                    $row['asset_name'] ?? ''
                )
            );

            $categoryName = trim(
                (string) (
                    $row['category'] ?? ''
                )
            );

            /*
            |--------------------------------------------------------------------------
            | DETEKSI BARIS KOSONG
            |--------------------------------------------------------------------------
            */

            $isEmpty = true;

            foreach ($rowArray as $value) {

                if (
                    $value !== null &&
                    trim((string) $value) !== ''
                ) {
                    $isEmpty = false;
                    break;
                }
            }

            if ($isEmpty) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | SIMPAN NOMOR ROW LOGICAL
            |--------------------------------------------------------------------------
            */

            $preparedRows[] = [
                'row_number' => $this->importRowNumber,
                'data' => $rowArray,
                'asset_name' => $assetName,
                'category_name' => $categoryName,
            ];

            $this->importRowNumber++;
        }

        if (empty($preparedRows)) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | LOAD EXISTING SERIAL NUMBERS
        |--------------------------------------------------------------------------
        |
        | Kita ambil semua serial number dari chunk ini sekaligus.
        |
        */

        $serialNumbersToCheck = [];

        foreach ($preparedRows as $prepared) {

            $serialNumber = $this->cleanString(
                $prepared['data']['serial_number'] ?? null
            );

            if ($serialNumber !== null) {

                $serialNumbersToCheck[] =
                    $serialNumber;
            }
        }

        $existingSerialNumbers = [];

        if (!empty($serialNumbersToCheck)) {

            $existingAssets =
                Asset::where(
                    'company_id',
                    $this->companyId
                )
                ->whereIn(
                    'serial_number',
                    array_values(
                        array_unique(
                            $serialNumbersToCheck
                        )
                    )
                )
                ->whereNotNull(
                    'serial_number'
                )
                ->pluck(
                    'serial_number'
                );

            foreach ($existingAssets as $serialNumber) {

                $key = $this->normalizeSerialNumber(
                    $serialNumber
                );

                if ($key !== '') {

                    $existingSerialNumbers[$key] = true;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | INSERT DATA
        |--------------------------------------------------------------------------
        */

        $insertRows = [];

        $errorRows = [];

        foreach ($preparedRows as $prepared) {

            $row = $prepared['data'];

            $rowNumber = $prepared['row_number'];

            $assetName = $prepared['asset_name'];

            $errors = [];

            /*
            |--------------------------------------------------------------------------
            | BASIC VALIDATION
            |--------------------------------------------------------------------------
            */

            if ($assetName === '') {

                $errors[] =
                    'Asset Name wajib diisi.';
            }

            if ($prepared['category_name'] === '') {

                $errors[] =
                    'Category wajib diisi.';
            }

            /*
            |--------------------------------------------------------------------------
            | SERIAL NUMBER
            |--------------------------------------------------------------------------
            */

            $serialNumber =
                $this->cleanString(
                    $row['serial_number'] ?? null
                );

            /*
            |--------------------------------------------------------------------------
            | VALIDASI DUPLICATE SERIAL
            |--------------------------------------------------------------------------
            |
            | Serial kosong = boleh.
            |
            */

            if ($serialNumber !== null) {

                $serialKey =
                    $this->normalizeSerialNumber(
                        $serialNumber
                    );

                /*
                |--------------------------------------------------------------------------
                | DUPLICATE DI DATABASE
                |--------------------------------------------------------------------------
                */

                if (
                    isset(
                        $existingSerialNumbers[$serialKey]
                    )
                ) {

                    $errors[] =
                        'Serial Number "' .
                        $serialNumber .
                        '" sudah terdaftar.';
                }

                /*
                |--------------------------------------------------------------------------
                | DUPLICATE DALAM FILE IMPORT
                |--------------------------------------------------------------------------
                */

                if (
                    isset(
                        $this->importSerialNumbers[$serialKey]
                    )
                ) {

                    $errors[] =
                        'Serial Number "' .
                        $serialNumber .
                        '" duplicate pada file import.';
                }
            }

            /*
            |--------------------------------------------------------------------------
            | RESOLVE CATEGORY
            |--------------------------------------------------------------------------
            */

            $category = null;

            if (
                $prepared['category_name'] !== ''
            ) {

                try {

                    $category =
                        $this->resolveCategory(
                            $prepared['category_name']
                        );

                } catch (\Throwable $e) {

                    $errors[] =
                        'Category gagal diproses: ' .
                        $e->getMessage();
                }
            }

            /*
            |--------------------------------------------------------------------------
            | SUB CATEGORY
            |--------------------------------------------------------------------------
            */

            $subCategory = null;

            if ($category) {

                try {

                    $subCategory =
                        $this->resolveSubCategory(
                            $category,
                            $row['sub_category'] ?? null
                        );

                } catch (\Throwable $e) {

                    $errors[] =
                        'Sub Category gagal diproses: ' .
                        $e->getMessage();
                }
            }

            /*
            |--------------------------------------------------------------------------
            | VENDOR
            |--------------------------------------------------------------------------
            */

            $vendor = null;

            try {

                $vendor =
                    $this->resolveVendor(
                        $row['vendor'] ?? null,
                        $row['vendor_address'] ?? null
                    );

            } catch (\Throwable $e) {

                $errors[] =
                    'Vendor gagal diproses: ' .
                    $e->getMessage();
            }

            /*
            |--------------------------------------------------------------------------
            | RESPONSIBLE USER
            |--------------------------------------------------------------------------
            */

            $responsibleUserId = null;

            try {

                $responsibleUserId =
                    $this->resolveResponsibleUserId(
                        $row['id_person_nik'] ?? null
                    );

            } catch (\Throwable $e) {

                $errors[] =
                    $e->getMessage();
            }

            /*
            |--------------------------------------------------------------------------
            | DATE HELPER
            |--------------------------------------------------------------------------
            */

            $parseDate = function ($value) use (
                $assetName
            ) {

                if (
                    $value === null ||
                    trim((string) $value) === ''
                ) {
                    return null;
                }

                try {

                    /*
                    |--------------------------------------------------------------------------
                    | EXCEL SERIAL DATE
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
                    | NORMAL DATE
                    |--------------------------------------------------------------------------
                    */

                    return Carbon::parse(
                        $value
                    )->format('Y-m-d');

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
            | DATE
            |--------------------------------------------------------------------------
            */

            $purchaseDate = null;

            $depreciationStartDate = null;

            $warrantyStart = null;

            $warrantyEnd = null;

            try {

                $purchaseDate =
                    $parseDate(
                        $row['purchase_date'] ?? null
                    );

                $depreciationStartDate =
                    $parseDate(
                        $row['depreciation_start_date'] ?? null
                    );

                $warrantyStart =
                    $parseDate(
                        $row['warranty_start'] ?? null
                    );

                $warrantyEnd =
                    $parseDate(
                        $row['warranty_end'] ?? null
                    );

            } catch (\Throwable $e) {

                $errors[] =
                    $e->getMessage();
            }

            /*
            |--------------------------------------------------------------------------
            | MAINTENANCE
            |--------------------------------------------------------------------------
            */

            $maintenanceRequired =
                strtolower(
                    trim(
                        (string) (
                            $row['maintenance_required']
                            ?? 'no'
                        )
                    )
                ) === 'yes';

            $maintenanceType = null;

            $maintenanceTrigger = null;

            $maintenanceInterval = null;

            $maintenanceIntervalUnit = null;

            $maintenanceStartDate = null;

            /*
            |--------------------------------------------------------------------------
            | MAINTENANCE DATA
            |--------------------------------------------------------------------------
            */

            if ($maintenanceRequired) {

                $maintenanceType =
                    $this->cleanString(
                        $row['maintenance_type'] ?? null
                    );

                $maintenanceTrigger =
                    $this->cleanString(
                        $row['maintenance_trigger'] ?? null
                    );

                $maintenanceInterval =
                    $this->cleanNumber(
                        $row['maintenance_interval'] ?? null
                    );

                $maintenanceIntervalUnit =
                    $this->cleanString(
                        $row['maintenance_interval_unit']
                        ?? null
                    );

                try {

                    $maintenanceStartDate =
                        $parseDate(
                            $row['maintenance_start_date']
                            ?? null
                        );

                } catch (\Throwable $e) {

                    $errors[] =
                        $e->getMessage();
                }

                /*
                |--------------------------------------------------------------------------
                | VALIDASI MAINTENANCE
                |--------------------------------------------------------------------------
                */

                if (!$maintenanceType) {

                    $errors[] =
                        'Maintenance Type wajib diisi jika Maintenance Required = yes.';
                }

                if (!$maintenanceTrigger) {

                    $errors[] =
                        'Maintenance Trigger wajib diisi jika Maintenance Required = yes.';
                }

                if (
                    $maintenanceInterval === null ||
                    (int) $maintenanceInterval <= 0
                ) {

                    $errors[] =
                        'Maintenance Interval wajib diisi dan harus lebih dari 0 jika Maintenance Required = yes.';
                }

                if (!$maintenanceIntervalUnit) {

                    $errors[] =
                        'Maintenance Interval Unit wajib diisi jika Maintenance Required = yes.';
                }

                if (!$maintenanceStartDate) {

                    $errors[] =
                        'Maintenance Start Date wajib diisi jika Maintenance Required = yes.';
                }
            }

            /*
            |--------------------------------------------------------------------------
            | NEXT MAINTENANCE
            |--------------------------------------------------------------------------
            */

            $nextMaintenanceDate =
                $this->calculateNextMaintenanceDate(
                    $maintenanceRequired,
                    $maintenanceStartDate,
                    $maintenanceInterval,
                    $maintenanceIntervalUnit
                );

            /*
            |--------------------------------------------------------------------------
            | CONDITION
            |--------------------------------------------------------------------------
            */

            $condition =
                strtolower(
                    trim(
                        (string) (
                            $row['condition']
                            ?? 'new'
                        )
                    )
                );

            if (
                !in_array(
                    $condition,
                    [
                        'new',
                        'used',
                    ],
                    true
                )
            ) {

                $errors[] =
                    'Condition harus new atau used.';
            }

            /*
            |--------------------------------------------------------------------------
            | DEPRECIATION METHOD
            |--------------------------------------------------------------------------
            */

            $depreciationMethod =
                $this->cleanString(
                    $row['depreciation_method']
                    ?? 'straight_line'
                );

            if (
                $depreciationMethod !== null &&
                $depreciationMethod !== 'straight_line'
            ) {

                $errors[] =
                    'Depreciation Method tidak valid.';
            }

            /*
            |--------------------------------------------------------------------------
            | JIKA ADA ERROR
            |--------------------------------------------------------------------------
            */

            if (!empty($errors)) {

                $errorRows[] = [

                    'import_history_id' =>
                        $this->history->id,

                    'row_number' =>
                        $rowNumber,

                    'data' =>
                        json_encode(
                            $row,
                            JSON_UNESCAPED_UNICODE |
                            JSON_UNESCAPED_SLASHES
                        ),

                    'error_message' =>
                        implode(
                            ' ',
                            $errors
                        ),

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ];

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | MARK SERIAL NUMBER AS PROCESSED
            |--------------------------------------------------------------------------
            */

            if ($serialNumber !== null) {

                $serialKey =
                    $this->normalizeSerialNumber(
                        $serialNumber
                    );

                $this->importSerialNumbers[
                    $serialKey
                ] = true;
            }

            /*
            |--------------------------------------------------------------------------
            | ASSET CODE
            |--------------------------------------------------------------------------
            */

            $assetCode =
                $this->generateAssetCode();

            /*
            |--------------------------------------------------------------------------
            | INSERT ARRAY
            |--------------------------------------------------------------------------
            */

            $insertRows[] = [

                /*
                |--------------------------------------------------------------------------
                | BASIC
                |--------------------------------------------------------------------------
                */

                'company_id' =>
                    $this->companyId,

                'category_id' =>
                    $category->id,

                'sub_category_id' =>
                    $subCategory?->id,

                'vendor_id' =>
                    $vendor?->id,

                'responsible_user_id' =>
                    $responsibleUserId,

                'asset_code' =>
                    $assetCode,

                'asset_name' =>
                    $assetName,

                /*
                |--------------------------------------------------------------------------
                | DETAIL
                |--------------------------------------------------------------------------
                */

                'description' =>
                    $this->cleanString(
                        $row['description'] ?? null
                    ),

                'asset_condition' =>
                    $condition,

                'brand' =>
                    $this->cleanString(
                        $row['brand'] ?? null
                    ),

                'model' =>
                    $this->cleanString(
                        $row['model'] ?? null
                    ),

                'serial_number' =>
                    $serialNumber,

                /*
                |--------------------------------------------------------------------------
                | PURCHASE
                |--------------------------------------------------------------------------
                */

                'purchase_date' =>
                    $purchaseDate,

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
                    $depreciationMethod,

                'useful_life' =>
                    $this->cleanNumber(
                        $row['useful_life'] ?? null
                    ),

                'residual_value' =>
                    $this->cleanNumber(
                        $row['residual_value'] ?? null
                    ),

                'depreciation_start_date' =>
                    $depreciationStartDate,

                /*
                |--------------------------------------------------------------------------
                | WARRANTY
                |--------------------------------------------------------------------------
                */

                'warranty_start' =>
                    $warrantyStart,

                'warranty_end' =>
                    $warrantyEnd,

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

                'status' =>
                    1,

                'qr_token' =>
                    Str::uuid()->toString(),

                'qr_generated_at' =>
                    now(),

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | BULK INSERT
        |--------------------------------------------------------------------------
        */

        if (!empty($insertRows)) {

            DB::table('assets')->insert(
                $insertRows
            );
        }

        /*
        |--------------------------------------------------------------------------
        | INSERT ERRORS
        |--------------------------------------------------------------------------
        */

        if (!empty($errorRows)) {

            DB::table('import_errors')->insert(
                $errorRows
            );
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE HISTORY
        |--------------------------------------------------------------------------
        */

        $successCount =
            count($insertRows);

        $failedCount =
            count($errorRows);

        if ($successCount > 0) {

            $this->history->increment(
                'success_rows',
                $successCount
            );
        }

        if ($failedCount > 0) {

            $this->history->increment(
                'failed_rows',
                $failedCount
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE SERIAL NUMBER
    |--------------------------------------------------------------------------
    */

    protected function normalizeSerialNumber(
        $value
    ): string {

        return strtolower(
            trim(
                (string) $value
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVE CATEGORY
    |--------------------------------------------------------------------------
    */

    protected function resolveCategory(
        string $categoryName
    ): Category {

        $categoryName =
            trim($categoryName);

        if ($categoryName === '') {

            throw new \Exception(
                'Category wajib diisi.'
            );
        }

        $categoryKey =
            strtolower($categoryName);

        if (
            isset(
                $this->categoryCache[$categoryKey]
            )
        ) {

            return $this->categoryCache[
                $categoryKey
            ];
        }

        $category =
            Category::where(
                'company_id',
                $this->companyId
            )
            ->whereRaw(
                'LOWER(TRIM(category_name)) = ?',
                [$categoryKey]
            )
            ->first();

        if (!$category) {

            $category =
                Category::create([

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
                        strtoupper(
                            $categoryName
                        ),

                    'description' =>
                        null,

                    'status' =>
                        1,
                ]);
        }

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

        $subCategoryName =
            trim(
                (string) $subCategoryName
            );

        if ($subCategoryName === '') {

            return null;
        }

        $subCategoryKey =
            $category->id .
            '|' .
            strtolower(
                $subCategoryName
            );

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

        $subCategory =
            SubCategory::where(
                'company_id',
                $this->companyId
            )
            ->where(
                'category_id',
                $category->id
            )
            ->whereRaw(
                'LOWER(TRIM(sub_category_name)) = ?',
                [
                    strtolower(
                        $subCategoryName
                    ),
                ]
            )
            ->first();

        if (!$subCategory) {

            $subCategory =
                SubCategory::create([

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
                        strtoupper(
                            $subCategoryName
                        ),

                    'description' =>
                        null,

                    'status' =>
                        1,
                ]);
        }

        $this->subCategoryCache[
            $subCategoryKey
        ] = $subCategory;

        return $subCategory;
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVE VENDOR
    |--------------------------------------------------------------------------
    */

    protected function resolveVendor(
        $vendorName,
        $vendorAddress
    ): ?Vendor {

        $vendorName =
            trim(
                (string) $vendorName
            );

        $vendorAddress =
            trim(
                (string) $vendorAddress
            );

        if ($vendorName === '') {

            return null;
        }

        $vendorKey =
            strtolower($vendorName) .
            '|' .
            strtolower($vendorAddress);

        if (
            isset(
                $this->vendorCache[
                    $vendorKey
                ]
            )
        ) {

            return $this->vendorCache[
                $vendorKey
            ];
        }

        $vendorQuery =
            Vendor::where(
                'company_id',
                $this->companyId
            )
            ->whereRaw(
                'LOWER(TRIM(vendor_name)) = ?',
                [
                    strtolower(
                        $vendorName
                    ),
                ]
            );

        if ($vendorAddress !== '') {

            $vendorQuery->whereRaw(
                'LOWER(TRIM(COALESCE(address, ""))) = ?',
                [
                    strtolower(
                        $vendorAddress
                    ),
                ]
            );

        } else {

            $vendorQuery->where(
                function ($query) {

                    $query
                        ->whereNull(
                            'address'
                        )
                        ->orWhereRaw(
                            'TRIM(address) = ?',
                            ['']
                        );
                }
            );
        }

        $vendor =
            $vendorQuery->first();

        if (!$vendor) {

            $vendor =
                Vendor::create([

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

                    'email' =>
                        null,

                    'status' =>
                        1,
                ]);
        }

        $this->vendorCache[
            $vendorKey
        ] = $vendor;

        return $vendor;
    }

    /*
    |--------------------------------------------------------------------------
    | USER CACHE
    |--------------------------------------------------------------------------
    */

    protected function initializeUserCache(): void
    {
        if ($this->userCacheInitialized) {

            return;
        }

        $users =
            User::where(
                'company_id',
                $this->companyId
            )
            ->whereNotNull('nik')
            ->get([
                'id',
                'nik',
            ]);

        foreach ($users as $user) {

            $nik =
                trim(
                    (string) $user->nik
                );

            if ($nik === '') {
                continue;
            }

            $this->userCache[
                $nik
            ] = (int) $user->id;
        }

        $this->userCacheInitialized = true;
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVE RESPONSIBLE USER
    |--------------------------------------------------------------------------
    */

    protected function resolveResponsibleUserId(
        $nik
    ): ?int {

        $nik =
            trim(
                (string) $nik
            );

        if ($nik === '') {

            return null;
        }

        if (
            !isset(
                $this->userCache[$nik]
            )
        ) {

            throw new \Exception(
                'User dengan ID Person (NIK) "' .
                $nik .
                '" tidak ditemukan.'
            );
        }

        return (int) $this->userCache[
            $nik
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | NEXT MAINTENANCE DATE
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

        $interval =
            (int) $interval;

        if ($interval <= 0) {

            return null;
        }

        $unit =
            strtolower(
                trim(
                    (string) $intervalUnit
                )
            );

        $date =
            Carbon::parse(
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

        return $date->format(
            'Y-m-d'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE ASSET CODE
    |--------------------------------------------------------------------------
    */

    protected function generateAssetCode(): string
    {
        if (
            !$this->assetCodeInitialized
        ) {

            $lastNumber =
                Asset::withTrashed()
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
                                SUBSTRING(
                                    asset_code,
                                    5
                                )
                                AS UNSIGNED
                            )
                        ) AS max_number
                    ")
                    ->value(
                        'max_number'
                    );

            $this->assetCodeCounter =
                $lastNumber !== null
                    ? (
                        (int) $lastNumber + 1
                    )
                    : 0;

            $this->assetCodeInitialized =
                true;
        }

        $code =
            'AST-' .
            str_pad(
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

    protected function cleanString(
        $value
    ): ?string {

        if ($value === null) {

            return null;
        }

        $value =
            trim(
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
    */

    protected function cleanNumber(
        $value
    ) {

        if (
            $value === null ||
            trim((string) $value) === ''
        ) {

            return null;
        }

        if (
            is_int($value) ||
            is_float($value) ||
            is_numeric($value)
        ) {

            return $value;
        }

        $value =
            trim(
                (string) $value
            );

        $value =
            preg_replace(
                '/[^\d,.\-]/',
                '',
                $value
            );

        if ($value === '') {

            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | INDONESIAN FORMAT
        |
        | 12.500.000
        |--------------------------------------------------------------------------
        */

        if (
            substr_count(
                $value,
                '.'
            ) > 1 &&
            strpos(
                $value,
                ','
            ) === false
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
        | 12.500,50
        |--------------------------------------------------------------------------
        */

        elseif (
            strpos(
                $value,
                '.'
            ) !== false &&
            strpos(
                $value,
                ','
            ) !== false
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

        /*
        |--------------------------------------------------------------------------
        | 12,500
        |--------------------------------------------------------------------------
        */

        elseif (
            strpos(
                $value,
                ','
            ) !== false
        ) {

            $value =
                str_replace(
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
    */

    public function chunkSize(): int
    {
        return 500;
    }
}