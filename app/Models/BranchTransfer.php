<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mutasi aset antar-cabang.
 *   sent      : sudah dikirim, aset masih tercatat di cabang asal
 *   received  : diterima cabang tujuan, aset pindah cabang
 *   cancelled : dibatalkan sebelum diterima
 */
class BranchTransfer extends Model
{
    use \App\Models\Concerns\Auditable;

    protected string $auditModule = 'branch_transfer';

    public const STATUS_SENT = 'sent';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_SENT => 'Dalam pengiriman',
        self::STATUS_RECEIVED => 'Diterima',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    protected $fillable = [
        'company_id',
        'transfer_no',
        'from_branch_id',
        'to_branch_id',
        'status',
        'notes',
        'receive_notes',
        'sent_at',
        'received_at',
        'cancelled_at',
        'sent_by',
        'received_by',
        'cancelled_by',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function auditLabel(): ?string
    {
        return $this->transfer_no;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function items(): HasMany
    {
        return $this->hasMany(BranchTransferItem::class);
    }

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id')->withTrashed();
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id')->withTrashed();
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
