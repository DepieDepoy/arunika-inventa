<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cabang perusahaan. Setiap perusahaan selalu punya satu Kantor Pusat
 * (is_head_office) yang tidak bisa dihapus. Jumlah cabang dibatasi paket
 * (plans.max_branches), Kantor Pusat ikut dihitung.
 */
class Branch extends Model
{
    use SoftDeletes;
    use \App\Models\Concerns\Auditable;

    protected string $auditModule = 'branch';

    protected $fillable = [
        'company_id',
        'branch_code',
        'branch_name',
        'address',
        'phone',
        'pic_name',
        'is_head_office',
        // Cabang yang teknisinya menangani maintenance cabang ini (NULL = otomatis).
        'service_branch_id',
        'status',
    ];

    protected $casts = [
        'is_head_office' => 'boolean',
        'status' => 'integer',
    ];

    public function auditLabel(): ?string
    {
        return $this->branch_name;
    }

    public function serviceBranch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class, 'service_branch_id')->withTrashed();
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function label(): string
    {
        return $this->branch_name . ($this->is_head_office ? ' (Pusat)' : '');
    }
}
