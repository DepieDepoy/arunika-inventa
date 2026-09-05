<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenance extends Model
{
    protected $fillable = [

        'company_id',
        'asset_id',
        'vendor_id',

        'maintenance_date',
        'maintenance_type',
        'maintenance_title',
        'description',

        'technician_name',
        'technician_phone',

        'cost',

        'result',
        'notes',

        'next_maintenance_date',

        'status',

        'documents',
        'photos',
    ];


    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */
    protected function casts(): array
    {
        return [

            'maintenance_date' => 'date',

            'next_maintenance_date' => 'date',

            'cost' => 'decimal:2',

            'documents' => 'array',

            'photos' => 'array',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Company
    |--------------------------------------------------------------------------
    */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }


    /*
    |--------------------------------------------------------------------------
    | Asset
    |--------------------------------------------------------------------------
    */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }


    /*
    |--------------------------------------------------------------------------
    | Vendor
    |--------------------------------------------------------------------------
    */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}