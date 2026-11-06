<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackofficeAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'backoffice_admin_id',
        'admin_name',
        'event',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(BackofficeAdmin::class, 'backoffice_admin_id');
    }
}
