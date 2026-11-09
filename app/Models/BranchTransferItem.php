<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchTransferItem extends Model
{
    protected $fillable = [
        'branch_transfer_id',
        'asset_id',
        'asset_code',
        'asset_name',
        'from_location',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(BranchTransfer::class, 'branch_transfer_id');
    }

    /**
     * Aset tanpa filter cabang: cabang tujuan tetap bisa melihat aset yang
     * sedang dikirim kepadanya.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class)->withoutGlobalScope(BranchScope::class)->withTrashed();
    }
}
