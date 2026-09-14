<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'asset_id',
        'requested_by',
        'handled_by',
        'request_type',
        'description',
        'status',
        'handled_at',
        'completed_at',
    ];

    protected $casts = [
        'handled_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];


    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Company pemilik request.
     */
    public function company()
    {
        return $this->belongsTo(
            Company::class,
            'company_id'
        );
    }


    /**
     * Asset yang diminta untuk maintenance / repair.
     */
    public function asset()
    {
        return $this->belongsTo(
            Asset::class,
            'asset_id'
        );
    }


    /**
     * User yang membuat request.
     */
    public function requester()
    {
        return $this->belongsTo(
            User::class,
            'requested_by'
        );
    }


    /**
     * User dari tim maintenance yang menangani request.
     */
    public function handler()
    {
        return $this->belongsTo(
            User::class,
            'handled_by'
        );
    }


    /**
     * Hasil maintenance yang dibuat ketika
     * request ini diselesaikan.
     *
     * Relasi:
     *
     * maintenance_requests.id
     *          ↓
     * maintenances.maintenance_request_id
     */
    public function maintenance()
    {
        return $this->hasOne(
            Maintenance::class,
            'maintenance_request_id'
        );
    }


    /**
     * Progress / activity log dari request.
     *
     * Setiap progress dapat memiliki beberapa foto.
     */
    public function logs()
    {
        return $this->hasMany(
            MaintenanceRequestLog::class,
            'maintenance_request_id'
        )
        ->with([
            'user',
            'photos',
        ])
        ->latest();
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Status yang dianggap masih aktif.
     *
     * pending
     * in_progress
     *
     * approved sudah tidak digunakan.
     */
    public function isActive(): bool
    {
        return in_array(
            $this->status,
            [
                'pending',
                'in_progress',
            ],
            true
        );
    }


    /**
     * Apakah request masih bisa diedit?
     *
     * Hanya request pending.
     */
    public function isEditable(): bool
    {
        return $this->status === 'pending';
    }


    /**
     * Apakah request sudah selesai?
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }


    /**
     * Apakah request sedang dikerjakan?
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }


    /**
     * Apakah request masih menunggu teknisi?
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
