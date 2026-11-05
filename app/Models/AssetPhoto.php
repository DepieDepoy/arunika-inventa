<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetPhoto extends Model
{
    use \App\Models\Concerns\Auditable;

    protected string $auditModule = 'asset_photo';

    public function auditCompanyId(): ?int
    {
        return $this->asset?->company_id;
    }

    public function auditLabel(): ?string
    {
        return $this->asset?->asset_name;
    }

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