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
    use SoftDeletes;

    protected $fillable = [
        'company_id',
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
        'status',

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
    ];

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
        return $this->hasMany(AssetMaintenance::class);
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