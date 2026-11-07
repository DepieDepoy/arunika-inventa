<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat perpindahan lokasi aset (dari, ke, kapan, oleh siapa).
 */
class AssetLocationHistory extends Model
{
    use \App\Models\Concerns\Auditable;

    protected string $auditModule = 'asset_location';

    protected $fillable = [
        'company_id',
        'asset_id',
        'from_location',
        'to_location',
        'moved_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'moved_at' => 'date',
    ];

    public function auditLabel(): ?string
    {
        return $this->asset?->asset_name;
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
