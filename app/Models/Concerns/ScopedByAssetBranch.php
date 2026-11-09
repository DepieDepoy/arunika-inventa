<?php

namespace App\Models\Concerns;

use App\Support\BranchAccess;
use Illuminate\Database\Eloquent\Builder;

/**
 * Untuk data turunan aset (maintenance, request, serah terima, penghapusan):
 * user cabang hanya melihat data yang asetnya ada di cabangnya.
 * Model wajib punya relasi asset().
 */
trait ScopedByAssetBranch
{
    public static function bootScopedByAssetBranch(): void
    {
        static::addGlobalScope('asset_branch', function (Builder $builder) {
            if (BranchAccess::accessibleBranchIds() !== null) {
                $builder->whereHas('asset', fn ($asset) => $asset->withTrashed());
            }
        });
    }
}
