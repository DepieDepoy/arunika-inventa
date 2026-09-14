<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequestLogPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_request_log_id',
        'file_path',
        'caption',
        'uploaded_by',
    ];

    public function log()
    {
        return $this->belongsTo(
            MaintenanceRequestLog::class,
            'maintenance_request_log_id'
        );
    }

    public function uploader()
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }
}