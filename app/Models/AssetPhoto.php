<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetPhoto extends Model
{
    protected $fillable = [
        'asset_id',
        'file_path',
        'original_name',
        'sort_order',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}