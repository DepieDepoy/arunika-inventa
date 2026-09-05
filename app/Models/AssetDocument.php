<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetDocument extends Model
{
    protected $fillable = [
        'asset_id',
        'document_type',
        'file_path',
        'original_name',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}