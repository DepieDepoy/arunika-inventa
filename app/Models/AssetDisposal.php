<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Penghapusan aset. Nilai (harga, nilai buku, hasil, laba/rugi) disimpan
 * sebagai snapshot pada saat penghapusan.
 */
class AssetDisposal extends Model
{
    use \App\Models\Concerns\Auditable;

    protected string $auditModule = 'asset_disposal';

    public const TYPES = [
        'scrapped' => 'Rusak & dimusnahkan',
        'lost' => 'Hilang / dicuri',
        'sold' => 'Dijual',
        'donated' => 'Dihibahkan / disumbangkan',
        'returned_vendor' => 'Dikembalikan ke vendor',
        'other' => 'Lainnya',
    ];

    /** Jenis yang biasanya menghasilkan uang / nilai pengganti. */
    public const WITH_PROCEEDS = ['sold', 'returned_vendor', 'other'];

    protected $fillable = [
        'company_id',
        'asset_id',
        'disposal_type',
        'disposal_date',
        'purchase_price',
        'book_value',
        'proceeds',
        'gain_loss',
        'holder_user_id',
        'previous_location',
        'previous_status',
        'notes',
        'document_path',
        'document_name',
        'created_by',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
    ];

    protected $casts = [
        'disposal_date' => 'date',
        'purchase_price' => 'decimal:2',
        'book_value' => 'decimal:2',
        'proceeds' => 'decimal:2',
        'gain_loss' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    public function auditLabel(): ?string
    {
        return $this->asset?->asset_name;
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class)->withTrashed();
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'holder_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('cancelled_at');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->disposal_type] ?? ucfirst((string) $this->disposal_type);
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }
}
