<?php

namespace App\Imports;

use App\Helpers\CodeHelper;
use App\Models\Asset;
use App\Models\Category;
use App\Models\ImportHistory;
use App\Models\Maintenance;
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
    protected int $companyId;

    protected ImportHistory $history;

    protected array $categoryCache = [];

    protected array $subCategoryCache = [];

    protected array $vendorCache = [];

    protected array $userCache = [];

    protected bool $userCacheInitialized = false;

    protected array $importSerialNumbers = [];

    protected int $assetCodeCounter = 0;

    protected bool $assetCodeInitialized = false;

    protected int $maintenanceCodeCounter = 0;

    protected bool $maintenanceCodeInitialized = false;

    protected array $generatedMaintenanceCodes = [];

    protected int $importRowNumber = 1;


    /**
     * ============================================================
     * CONSTRUCTOR
     * ============================================================
     */
    public function __construct(ImportHistory $history)
    {
        $this->history = $history;

        $this->companyId = (int) $history->company_id;

        $this->initializeUserCache();
    }


    /**
     * ============================================================
     * MAIN IMPORT
     * ============================================================
     */
    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $preparedRows = [];

        /**
         * --------------------------------------------------------
         * PREPARE ROWS
         * --------------------------------------------------------
         */
        foreach ($rows as $row) {

            $rowArray = $row->toArray();

            $assetName = trim(
                (string) ($row['asset_name'] ?? '')
            );

            $categoryName = trim(
                (string) ($row['category'] ?? '')
            );

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


        /**
         * --------------------------------------------------------
         * CHECK SERIAL NUMBER
         * --------------------------------------------------------
         */
        $serialNumbersToCheck = [];

        foreach ($preparedRows as $prepared) {

            $serialNumber = $this->cleanString(
                $prepared['data']['serial_number'] ?? null
            );

            if ($serialNumber !== null) {
                $serialNumbersToCheck[] = $serialNumber;
            }
        }

        $existingSerialNumbers = [];

        if (!empty($serialNumbersToCheck)) {

            $existingAssets = Asset::where(
                'company_id',
                $this->companyId
            )
                ->whereIn(
                    'serial_number',
                    array_values(
                        array_unique($serialNumbersToCheck)
                    )
                )
                ->whereNotNull('serial_number')
                ->pluck('serial_number');

            foreach ($existingAssets as $serialNumber) {

                $key = $this->normalizeSerialNumber(
                    $serialNumber
                );

                if ($key !== '') {
                    $existingSerialNumbers[$key] = true;
                }
            }
        }


        $insertRows = [];

        $errorRows = [];


        /**
         * ========================================================
         * PROCESS EACH ROW
         * ========================================================
         */
        foreach ($preparedRows as $prepared) {

            $row = $prepared['data'];

            $rowNumber = $prepared['row_number'];

            $assetName = $prepared['asset_name'];

            $errors = [];


            /**
             * ----------------------------------------------------
             * REQUIRED FIELD
             * ----------------------------------------------------
             */
            if ($assetName === '') {

                $errors[] =
                    'Asset Name wajib diisi.';
            }

            if ($prepared['category_name'] === '') {

                $errors[] =
                    'Category wajib diisi.';
            }


            /**
             * ----------------------------------------------------
             * SERIAL NUMBER
             * ----------------------------------------------------
             */
            $serialNumber = $this->cleanString(
                $row['serial_number'] ?? null
            );

            if ($serialNumber !== null) {

                $serialKey = $this->normalizeSerialNumber(
                    $serialNumber
                );

                if (isset($existingSerialNumbers[$serialKey])) {

                    $errors[] =
                        'Serial Number "' .
                        $serialNumber .
                        '" sudah terdaftar.';
                }

                if (isset($this->importSerialNumbers[$serialKey])) {

                    $errors[] =
                        'Serial Number "' .
                        $serialNumber .
                        '" duplicate pada file import.';
                }
            }


            /**
             * ----------------------------------------------------
             * CATEGORY
             * ----------------------------------------------------
             */
            $category = null;

            if ($prepared['category_name'] !== '') {

                try {

                    $category = $this->resolveCategory(
                        $prepared['category_name']
                    );

                } catch (\Throwable $e) {

                    $errors[] =
                        'Category gagal diproses: ' .
                        $e->getMessage();
                }
            }


            /**
             * ----------------------------------------------------
             * SUB CATEGORY
             * ----------------------------------------------------
             */
            $subCategory = null;

            if ($category) {

                try {

                    $subCategory = $this->resolveSubCategory(
                        $category,
                        $row['sub_category'] ?? null
                    );

                } catch (\Throwable $e) {

                    $errors[] =
                        'Sub Category gagal diproses: ' .
                        $e->getMessage();
                }
            }


            /**
             * ----------------------------------------------------
             * VENDOR
             * ----------------------------------------------------
             */
            $vendor = null;

            try {

                $vendor = $this->resolveVendor(
                    $row['vendor'] ?? null,
                    $row['vendor_address'] ?? null
                );

            } catch (\Throwable $e) {

                $errors[] =
                    'Vendor gagal diproses: ' .
                    $e->getMessage();
            }


            /**
             * ----------------------------------------------------
             * RESPONSIBLE USER BY NIK
             * ----------------------------------------------------
             */
            $responsibleUserId = null;

            try {

                $responsibleUserId =
                    $this->resolveResponsibleUserId(
                        $row['id_person_nik'] ?? null
                    );

            } catch (\Throwable $e) {

                $errors[] = $e->getMessage();
            }


            /**
             * ====================================================
             * DATE PARSER
             * ====================================================
             */
            $parseDate = function ($value) use ($assetName) {

                if (
                    $value === null ||
                    trim((string) $value) === ''
                ) {
                    return null;
                }

                try {

                    if (is_numeric($value)) {

                        return Carbon::createFromTimestamp(
                            \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp(
                                $value
                            )
                        )->format('Y-m-d');
                    }

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


            /**
             * ====================================================
             * PURCHASE / DEPRECIATION / WARRANTY DATE
             * ====================================================
             */
            $purchaseDate = null;

            $depreciationStartDate = null;

            $warrantyStart = null;

            $warrantyEnd = null;

            try {

                $purchaseDate = $parseDate(
                    $row['purchase_date'] ?? null
                );

                $depreciationStartDate = $parseDate(
                    $row['depreciation_start_date'] ?? null
                );

                $warrantyStart = $parseDate(
                    $row['warranty_start'] ?? null
                );

                $warrantyEnd = $parseDate(
                    $row['warranty_end'] ?? null
                );

            } catch (\Throwable $e) {

                $errors[] = $e->getMessage();
            }


            /**
             * ====================================================
             * MAINTENANCE
             * ====================================================
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
                        $row['maintenance_interval_unit'] ?? null
                    );


                try {

                    $maintenanceStartDate =
                        $parseDate(
                            $row['maintenance_start_date']
                            ?? null
                        );

                } catch (\Throwable $e) {

                    $errors[] = $e->getMessage();
                }


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


            /**
             * ----------------------------------------------------
             * CALCULATE NEXT MAINTENANCE DATE
             * ----------------------------------------------------
             */
            $nextMaintenanceDate =
                $this->calculateNextMaintenanceDate(
                    $maintenanceRequired,
                    $maintenanceStartDate,
                    $maintenanceInterval,
                    $maintenanceIntervalUnit
                );


            /**
             * ====================================================
             * CONDITION
             * ====================================================
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
                    ['new', 'used'],
                    true
                )
            ) {

                $errors[] =
                    'Condition harus new atau used.';
            }


            /**
             * ====================================================
             * DEPRECIATION METHOD
             * ====================================================
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


            /**
             * ====================================================
             * SAVE ERROR ROW
             * ====================================================
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


            /**
             * ====================================================
             * MARK SERIAL AS USED IN THIS IMPORT
             * ====================================================
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


            /**
             * ====================================================
             * GENERATE ASSET CODE
             * ====================================================
             */
            $assetCode =
                $this->generateAssetCode();


            /**
             * ====================================================
             * PREPARE ASSET INSERT
             * ====================================================
             */
            $insertRows[] = [

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

                'warranty_start' =>
                    $warrantyStart,

                'warranty_end' =>
                    $warrantyEnd,

                'warranty_note' =>
                    $this->cleanString(
                        $row['warranty_note'] ?? null
                    ),

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

                'location' =>
                    $this->cleanString(
                        $row['location'] ?? null
                    ),

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


        /**
         * ========================================================
         * INSERT ASSETS
         * ========================================================
         */
        if (!empty($insertRows)) {

            DB::table('assets')->insert(
                $insertRows
            );
        }


        /**
         * ========================================================
         * CREATE MAINTENANCE SCHEDULE
         *
         * Hanya asset yang:
         *
         * maintenance_required = yes
         * dan memiliki next_maintenance_date
         * ========================================================
         */
        if (!empty($insertRows)) {

            $assetCodes =
                array_column(
                    $insertRows,
                    'asset_code'
                );


            $insertedAssets =
                Asset::where(
                    'company_id',
                    $this->companyId
                )
                    ->whereIn(
                        'asset_code',
                        $assetCodes
                    )
                    ->get([
                        'id',
                        'company_id',
                        'asset_code',
                        'vendor_id',
                        'maintenance_required',
                        'maintenance_type',
                        'next_maintenance_date',
                    ]);


            $maintenanceRows = [];


            foreach ($insertedAssets as $asset) {

                /**
                 * -----------------------------------------------
                 * SKIP JIKA MAINTENANCE TIDAK DIPERLUKAN
                 * -----------------------------------------------
                 */
                if (
                    !(bool) $asset->maintenance_required
                ) {
                    continue;
                }


                /**
                 * -----------------------------------------------
                 * SKIP JIKA BELUM ADA TANGGAL
                 * -----------------------------------------------
                 */
                if (
                    empty(
                        $asset->next_maintenance_date
                    )
                ) {
                    continue;
                }


                /**
                 * -----------------------------------------------
                 * GENERATE MAINTENANCE CODE
                 *
                 * PENTING:
                 * Jangan gunakan CodeHelper::generateNumber()
                 * di dalam loop karena semua row belum masuk DB.
                 *
                 * Sekarang menggunakan counter internal yang
                 * aman untuk satu batch/chunk.
                 * -----------------------------------------------
                 */
                $maintenanceCode =
                    $this->generateMaintenanceCode();


                /**
                 * -----------------------------------------------
                 * PREPARE MAINTENANCE
                 * -----------------------------------------------
                 */
                $maintenanceRows[] = [

                    'company_id' =>
                        $this->companyId,

                    'asset_id' =>
                        $asset->id,

                    'maintenance_code' =>
                        $maintenanceCode,

                    'maintenance_type' =>
                        $asset->maintenance_type,

                    'maintenance_date' =>
                        $asset->next_maintenance_date,

                    'problem_description' =>
                        null,

                    'action_taken' =>
                        null,

                    'technician_name' =>
                        null,

                    'vendor_id' =>
                        $asset->vendor_id,

                    'cost' =>
                        0,

                    'status' =>
                        'scheduled',

                    'next_maintenance_date' =>
                        $asset->next_maintenance_date,

                    'notes' =>
                        null,

                    'created_by' =>
                        null,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ];
            }


            /**
             * ----------------------------------------------------
             * INSERT MAINTENANCE
             * ----------------------------------------------------
             */
            if (!empty($maintenanceRows)) {

                DB::table('maintenances')->insert(
                    $maintenanceRows
                );
            }
        }


        /**
         * ========================================================
         * INSERT IMPORT ERRORS
         * ========================================================
         */
        if (!empty($errorRows)) {

            DB::table('import_errors')->insert(
                $errorRows
            );
        }


        /**
         * ========================================================
         * UPDATE IMPORT HISTORY
         * ========================================================
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


    /**
     * ============================================================
     * NORMALIZE SERIAL NUMBER
     * ============================================================
     */
    protected function normalizeSerialNumber(
        $value
    ): string {

        return strtoupper(
            trim(
                preg_replace(
                    '/\s+/',
                    '',
                    (string) $value
                )
            )
        );
    }


    /**
     * ============================================================
     * RESOLVE CATEGORY
     * ============================================================
     */
    protected function resolveCategory(
        string $categoryName
    ): Category {

        $name =
            strtoupper(
                trim($categoryName)
            );


        $key =
            mb_strtolower($name);


        if (
            isset(
                $this->categoryCache[$key]
            )
        ) {

            return $this->categoryCache[$key];
        }


        $category =
            Category::where(
                'company_id',
                $this->companyId
            )
                ->whereRaw(
                    'LOWER(category_name) = ?',
                    [$key]
                )
                ->first();


        if (!$category) {

            $category = new Category();

            $category->company_id =
                $this->companyId;

            $category->category_code =
                CodeHelper::generateNumber(
                    'CAT-',
                    Category::class,
                    'category_code',
                    $this->companyId
                );

            $category->category_name =
                $name;

            $category->status =
                1;

            $category->save();
        }


        $this->categoryCache[$key] =
            $category;


        return $category;
    }


    /**
     * ============================================================
     * RESOLVE SUB CATEGORY
     * ============================================================
     */
    protected function resolveSubCategory(
        Category $category,
        $subCategoryName
    ): ?SubCategory {

        $name =
            $this->cleanString(
                $subCategoryName
            );


        if (!$name) {
            return null;
        }


        $name =
            strtoupper($name);


        $key =
            $category->id .
            '|' .
            mb_strtolower($name);


        if (
            isset(
                $this->subCategoryCache[$key]
            )
        ) {

            return $this->subCategoryCache[$key];
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
                    'LOWER(sub_category_name) = ?',
                    [
                        mb_strtolower($name)
                    ]
                )
                ->first();


        if (!$subCategory) {

            $subCategory =
                new SubCategory();

            $subCategory->company_id =
                $this->companyId;

            $subCategory->category_id =
                $category->id;

            $subCategory->sub_category_code =
                CodeHelper::generateNumber(
                    'SUBCAT-',
                    SubCategory::class,
                    'sub_category_code',
                    $this->companyId
                );

            $subCategory->sub_category_name =
                $name;

            $subCategory->status =
                1;

            $subCategory->save();
        }


        $this->subCategoryCache[$key] =
            $subCategory;


        return $subCategory;
    }


    /**
     * ============================================================
     * RESOLVE VENDOR
     * ============================================================
     */
    protected function resolveVendor(
        $vendorName,
        $vendorAddress
    ): ?Vendor {

        $name =
            $this->cleanString(
                $vendorName
            );

        $address =
            $this->cleanString(
                $vendorAddress
            );


        if (!$name) {
            return null;
        }


        $key =
            mb_strtolower($name);


        if (
            isset(
                $this->vendorCache[$key]
            )
        ) {

            return $this->vendorCache[$key];
        }


        $vendor =
            Vendor::where(
                'company_id',
                $this->companyId
            )
                ->whereRaw(
                    'LOWER(vendor_name) = ?',
                    [$key]
                )
                ->first();


        if (!$vendor) {

            $vendor =
                new Vendor();

            $vendor->company_id =
                $this->companyId;

            $vendor->vendor_code =
                CodeHelper::generateNumber(
                    'VD-',
                    Vendor::class,
                    'vendor_code',
                    $this->companyId
                );

            $vendor->vendor_name =
                $name;

            $vendor->address =
                $address;

            $vendor->status =
                1;

            $vendor->save();

        } else {

            /**
             * Jangan menimpa address vendor existing
             * jika data vendor sudah ada.
             */
        }


        $this->vendorCache[$key] =
            $vendor;


        return $vendor;
    }


    /**
     * ============================================================
     * INITIALIZE USER CACHE
     * ============================================================
     */
    protected function initializeUserCache(): void
    {
        if ($this->userCacheInitialized) {
            return;
        }


        $this->userCache = [];


        User::where(
            'company_id',
            $this->companyId
        )
            ->whereNotNull('nik')
            ->get([
                'id',
                'nik',
            ])
            ->each(function ($user) {

                $nik =
                    trim(
                        (string) $user->nik
                    );


                if ($nik === '') {
                    return;
                }


                $key =
                    mb_strtolower(
                        $nik
                    );


                $this->userCache[$key] =
                    $user->id;
            });


        $this->userCacheInitialized =
            true;
    }


    /**
     * ============================================================
     * RESOLVE RESPONSIBLE USER BY NIK
     * ============================================================
     */
    protected function resolveResponsibleUserId(
        $nik
    ): ?int {

        $nik =
            $this->cleanString(
                $nik
            );


        if (!$nik) {
            return null;
        }


        $key =
            mb_strtolower(
                $nik
            );


        if (
            isset(
                $this->userCache[$key]
            )
        ) {

            return (int)
                $this->userCache[$key];
        }


        throw new \Exception(
            'NIK "' .
            $nik .
            '" tidak ditemukan pada User perusahaan.'
        );
    }


    /**
     * ============================================================
     * CALCULATE NEXT MAINTENANCE DATE
     * ============================================================
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


    /**
     * ============================================================
     * GENERATE ASSET CODE
     * ============================================================
     */
    protected function generateAssetCode(): string
    {
        if (!$this->assetCodeInitialized) {

            $lastAssetCode =
                Asset::withTrashed()
                    ->where(
                        'company_id',
                        $this->companyId
                    )
                    ->where(
                        'asset_code',
                        'like',
                        'AST-%'
                    )
                    ->orderByDesc('id')
                    ->value(
                        'asset_code'
                    );


            if ($lastAssetCode) {

                preg_match(
                    '/AST-(\d+)/',
                    $lastAssetCode,
                    $matches
                );


                $this->assetCodeCounter =
                    isset($matches[1])
                    ? (int) $matches[1]
                    : 0;

            } else {

                $this->assetCodeCounter = 0;
            }


            $this->assetCodeInitialized =
                true;
        }


        do {

            $this->assetCodeCounter++;


            $code =
                'AST-' .
                str_pad(
                    (string) $this->assetCodeCounter,
                    6,
                    '0',
                    STR_PAD_LEFT
                );


            $exists =
                Asset::withTrashed()
                    ->where(
                        'company_id',
                        $this->companyId
                    )
                    ->where(
                        'asset_code',
                        $code
                    )
                    ->exists();

        } while ($exists);


        return $code;
    }


    /**
     * ============================================================
     * GENERATE MAINTENANCE CODE
     *
     * FIX DUPLICATE MNT-00
     *
     * ============================================================
     */
    protected function generateMaintenanceCode(): string
    {
        /**
         * Ambil nomor terakhir hanya SATU KALI
         * untuk satu instance import.
         */
        if (!$this->maintenanceCodeInitialized) {

            $lastMaintenanceCode =
                Maintenance::withTrashed()
                    ->where(
                        'company_id',
                        $this->companyId
                    )
                    ->where(
                        'maintenance_code',
                        'like',
                        'MNT-%'
                    )
                    ->orderByDesc('id')
                    ->value(
                        'maintenance_code'
                    );


            if ($lastMaintenanceCode) {

                preg_match(
                    '/MNT-(\d+)/',
                    $lastMaintenanceCode,
                    $matches
                );


                $this->maintenanceCodeCounter =
                    isset($matches[1])
                    ? (int) $matches[1]
                    : 0;

            } else {

                $this->maintenanceCodeCounter = 0;
            }


            $this->maintenanceCodeInitialized =
                true;
        }


        /**
         * Naikkan nomor sampai mendapatkan kode
         * yang belum dipakai.
         */
        do {

            $this->maintenanceCodeCounter++;


            $code =
                'MNT-' .
                str_pad(
                    (string) $this->maintenanceCodeCounter,
                    2,
                    '0',
                    STR_PAD_LEFT
                );


            /**
             * Cek ke database.
             */
            $existsInDatabase =
                Maintenance::withTrashed()
                    ->where(
                        'company_id',
                        $this->companyId
                    )
                    ->where(
                        'maintenance_code',
                        $code
                    )
                    ->exists();


            /**
             * Cek juga kode yang sudah dibuat
             * di batch/chunk ini tetapi belum diinsert.
             */
            $existsInBatch =
                isset(
                    $this->generatedMaintenanceCodes[$code]
                );


        } while (
            $existsInDatabase ||
            $existsInBatch
        );


        /**
         * Simpan ke memory supaya tidak
         * duplicate dalam batch.
         */
        $this->generatedMaintenanceCodes[$code] =
            true;


        return $code;
    }


    /**
     * ============================================================
     * CLEAN STRING
     * ============================================================
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


        if ($value === '') {
            return null;
        }


        return $value;
    }


    /**
     * ============================================================
     * CLEAN NUMBER
     * ============================================================
     */
    protected function cleanNumber($value)
    {
        if (
            $value === null ||
            trim((string) $value) === ''
        ) {
            return null;
        }


        if (is_numeric($value)) {
            return $value;
        }


        $value =
            str_replace(
                ['.', ','],
                ['', '.'],
                trim((string) $value)
            );


        return is_numeric($value)
            ? $value
            : null;
    }


    /**
     * ============================================================
     * CHUNK SIZE
     * ============================================================
     */
    public function chunkSize(): int
    {
        return 500;
    }
}