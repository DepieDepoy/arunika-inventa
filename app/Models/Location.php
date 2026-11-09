<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Lokasi di dalam cabang (ruangan / lantai / gudang).
 * Cabang = kantor/kota; Lokasi = tempat di dalam kantor tersebut.
 */
class Location extends Model
{
    use SoftDeletes;
    use \App\Models\Concerns\Auditable;

    protected string $auditModule = 'location';

    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function auditLabel(): ?string
    {
        return $this->name;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
