<?php

namespace App\Models\Scopes;

use App\Support\BranchAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Menyaring aset sesuai cabang user yang login (lihat BranchAccess):
 * cabang sendiri, ditambah cabang yang dilayani bila user teknisi.
 * Administrator Kantor Pusat / proses tanpa login: tidak disaring.
 */
class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $ids = BranchAccess::accessibleBranchIds();

        if ($ids !== null) {
            $builder->whereIn($model->qualifyColumn('branch_id'), $ids);
        }
    }
}
