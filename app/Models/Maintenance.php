<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Maintenance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'maintenance_request_id',
        'asset_id',
        'maintenance_code',
        'maintenance_type',
        'maintenance_date',
        'problem_description',
        'action_taken',
        'technician_name',
        'vendor_id',
        'cost',
        'status',
        'next_maintenance_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'maintenance_date'      => 'date',
        'next_maintenance_date' => 'date',
        'cost'                  => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company()
    {
        return $this->belongsTo(
            Company::class,
            'company_id'
        );
    }

    /**
     * Request maintenance yang menghasilkan record ini.
     */
    public function maintenanceRequest()
    {
        return $this->belongsTo(
            MaintenanceRequest::class,
            'maintenance_request_id'
        );
    }

    public function asset()
    {
        return $this->belongsTo(
            Asset::class,
            'asset_id'
        );
    }

    public function vendor()
    {
        return $this->belongsTo(
            Vendor::class,
            'vendor_id'
        );
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function photos()
    {
        return $this->hasMany(
            MaintenancePhoto::class,
            'maintenance_id'
        );
    }

    public function afterPhotos()
    {
        return $this->hasMany(
            MaintenancePhoto::class,
            'maintenance_id'
        )->where(
            'photo_type',
            'after'
        );
    }
}
