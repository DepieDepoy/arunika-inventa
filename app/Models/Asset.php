<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\AssetPhoto;
use App\Models\AssetDocument;
use App\Models\Maintenance;
use App\Models\MaintenanceRequest;

class Asset extends Model
{
    use \App\Models\Concerns\Auditable;

    protected string $auditModule = 'asset';

    use SoftDeletes;

    /**
     * Multi-cabang: user cabang hanya melihat aset cabangnya (BranchScope),
     * dan aset baru otomatis masuk cabang user / Kantor Pusat.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\BranchScope());

        static::creating(function (Asset $asset) {
            if (!$asset->branch_id && $asset->company_id) {
                $asset->branch_id = \App\Support\BranchAccess::defaultBranchId((int) $asset->company_id);
            }
        });

        /*
         * Master lokasi: teks `location` & `location_id` selalu selaras.
         *  - location_id diisi  -> teks = nama lokasi
         *  - teks diisi         -> dicocokkan / dibuat di master lokasi cabang aset
         *  - pindah cabang      -> lokasi lama (cabang lain) dilepas
         */
        static::saving(function (Asset $asset) {
            if ($asset->isDirty('location_id') && $asset->location_id) {
                $asset->location = Location::withTrashed()->whereKey($asset->location_id)->value('name') ?? $asset->location;

                return;
            }

            if ($asset->isDirty('location') || ($asset->location && !$asset->location_id)) {
                $asset->location = $asset->location !== null ? trim((string) $asset->location) : null;
                $asset->location_id = \App\Services\Asset\LocationService::resolveId(
                    (int) $asset->company_id,
                    $asset->branch_id ? (int) $asset->branch_id : null,
                    $asset->location
                );

                if (!$asset->location_id && $asset->location === '') {
                    $asset->location = null;
                }

                return;
            }

            if ($asset->isDirty('branch_id') && $asset->location_id) {
                $locationBranch = Location::withTrashed()->whereKey($asset->location_id)->value('branch_id');

                if ((int) $locationBranch !== (int) $asset->branch_id) {
                    $asset->location_id = null;
                    $asset->location = null;
                }
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function locationRef()
    {
        return $this->belongsTo(Location::class, 'location_id')->withTrashed();
    }

    protected $fillable = [
        'company_id',
        'branch_id',
        'category_id',
        'sub_category_id',
        'vendor_id',
        'responsible_user_id',

        'asset_code',
        'asset_name',
        'brand',
        'model',
        'serial_number',
        'description',
        'asset_condition',

        'purchase_date',
        'purchase_price',
        'purchase_invoice',

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

        'location',
        'location_id',
        'status',
        'disposed_at',

        'qr_token',
        'qr_generated_at',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'residual_value' => 'decimal:2',
        'depreciation_start_date' => 'date',
        'warranty_start' => 'date',
        'warranty_end' => 'date',
        'qr_generated_at' => 'datetime',
        'disposed_at' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    | 0 = tidak aktif, 1 = aktif, 2 = dihapuskan (lihat AssetDisposal).
    | Aset dihapuskan tetap tersimpan beserta riwayatnya, tetapi tidak dihitung
    | sebagai aset perusahaan, tidak bisa diubah, diserahkan, atau dipindahkan.
    */

    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_DISPOSED = 2;

    public const STATUS_LABELS = [
        self::STATUS_INACTIVE => 'Tidak aktif',
        self::STATUS_ACTIVE => 'Aktif',
        self::STATUS_DISPOSED => 'Dihapuskan',
    ];

    /** Batas hari "garansi segera habis". */
    public const WARRANTY_SOON_DAYS = 30;

    public function isDisposed(): bool
    {
        return (int) $this->status === self::STATUS_DISPOSED;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[(int) $this->status] ?? 'Tidak aktif';
    }

    /**
     * Aset yang masih dimiliki perusahaan (aktif / tidak aktif), tanpa yang dihapuskan.
     */
    public function scopeInService($query)
    {
        return $query->where($query->qualifyColumn('status'), '!=', self::STATUS_DISPOSED);
    }

    /**
     * Status garansi: none | active | expiring | expired, plus sisa hari.
     *
     * @return array{state: string, label: string, days_left: ?int}
     */
    public function warrantyStatus(): array
    {
        if (!$this->warranty_end) {
            return ['state' => 'none', 'label' => 'Tidak ada garansi', 'days_left' => null];
        }

        $days = (int) today()->diffInDays($this->warranty_end->copy()->startOfDay(), false);

        if ($days < 0) {
            return ['state' => 'expired', 'label' => 'Garansi habis', 'days_left' => $days];
        }

        if ($days <= self::WARRANTY_SOON_DAYS) {
            return ['state' => 'expiring', 'label' => $days === 0 ? 'Garansi habis hari ini' : "Garansi habis {$days} hari lagi", 'days_left' => $days];
        }

        return ['state' => 'active', 'label' => 'Masih garansi', 'days_left' => $days];
    }

    /**
     * Company
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Sub Category
     */
    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    /**
     * Vendor
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Responsible User
     */
    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * Assignment History
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class)
            ->orderByDesc('start_at');
    }

    /**
     * Current Assignment
     */
    public function currentAssignment()
    {
        return $this->hasOne(AssetAssignment::class)
            ->whereNull('end_at')
            ->latestOfMany('start_at');
    }
    public function photos(): HasMany
    {
        return $this->hasMany(AssetPhoto::class)
            ->orderBy('sort_order');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AssetDocument::class);
    }

    public function maintenances(): HasMany
    {
        // Riwayat maintenance memakai tabel `maintenances` (asset_maintenances sudah tidak diisi).
        return $this->hasMany(Maintenance::class);
    }

    public function locationHistories(): HasMany
    {
        return $this->hasMany(AssetLocationHistory::class)
            ->orderByDesc('moved_at')
            ->orderByDesc('id');
    }

    public function disposals(): HasMany
    {
        return $this->hasMany(AssetDisposal::class)->latest('id');
    }

    /** Penghapusan yang berlaku (belum dibatalkan). */
    public function activeDisposal()
    {
        return $this->hasOne(AssetDisposal::class)
            ->whereNull('cancelled_at')
            ->latestOfMany();
    }

    public function activeMaintenanceRequest()
    {
        return $this->hasOne(MaintenanceRequest::class)
            ->whereIn('status', [
                'pending',
                //'approved',
                'in_progress',
            ])
            ->latestOfMany();
    }
}