<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Petugas audit fisik + area lokasi tanggung jawabnya (NULL = semua lokasi).
 */
class AssetAuditAssignee extends Model
{
    protected $fillable = [
        'asset_audit_id',
        'user_id',
        'location_id',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(AssetAudit::class, 'asset_audit_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }
}
