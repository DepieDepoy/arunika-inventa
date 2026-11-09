<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAuditItem extends Model
{
    public const RESULT_FOUND = 'found';
    public const RESULT_MISSING = 'missing';

    public const CONDITIONS = [
        'good' => 'Baik',
        'minor_damage' => 'Rusak ringan',
        'major_damage' => 'Rusak berat',
    ];

    protected $fillable = [
        'asset_audit_id',
        'asset_id',
        'asset_code',
        'asset_name',
        'expected_location',
        'expected_branch_id',
        'expected_location_id',
        'in_scope',
        'result',
        'found_location',
        'found_location_id',
        'condition',
        'location_mismatch',
        'branch_mismatch',
        'notes',
        'checked_at',
        'checked_by',
    ];

    protected $casts = [
        'in_scope' => 'boolean',
        'location_mismatch' => 'boolean',
        'branch_mismatch' => 'boolean',
        'checked_at' => 'datetime',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(AssetAudit::class, 'asset_audit_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class)->withoutGlobalScope(BranchScope::class)->withTrashed();
    }

    public function expectedBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'expected_branch_id')->withTrashed();
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function conditionLabel(): ?string
    {
        return $this->condition ? (self::CONDITIONS[$this->condition] ?? $this->condition) : null;
    }

    /**
     * Status tampilan: pending | found | misplaced | missing | extra | other_branch
     */
    public function state(): string
    {
        if (!$this->in_scope) {
            return $this->branch_mismatch ? 'other_branch' : 'extra';
        }

        if ($this->result === self::RESULT_MISSING) {
            return 'missing';
        }

        if ($this->result === self::RESULT_FOUND) {
            return $this->location_mismatch ? 'misplaced' : 'found';
        }

        return 'pending';
    }
}
