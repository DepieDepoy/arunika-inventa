<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetDocument extends Model
{
    use \App\Models\Concerns\Auditable;

    protected string $auditModule = 'asset_document';

    public function auditCompanyId(): ?int
    {
        return $this->asset?->company_id;
    }

    public function auditLabel(): ?string
    {
        return trim(($this->asset?->asset_name ?? '') . ' - ' . $this->original_name, ' -');
    }

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