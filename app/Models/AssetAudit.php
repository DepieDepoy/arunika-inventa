<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sesi audit fisik aset (cek fisik / inventarisasi).
 * Saat dibuat, semua aset sesuai cakupan (cabang + lokasi) disalin ke
 * asset_audit_items sebagai "belum dicek". Petugas scan QR satu per satu;
 * saat diselesaikan, yang belum dicek otomatis menjadi "tidak ditemukan".
 */
class AssetAudit extends Model
{
    use \App\Models\Concerns\Auditable;

    protected string $auditModule = 'asset_audit';

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_IN_PROGRESS => 'Sedang berjalan',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    protected $fillable = [
        'company_id',
        'branch_id',
        'audit_no',
        'title',
        'location_filter',
        'status',
        'notes',
        'locations_applied',
        'started_by',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'locations_applied' => 'boolean',
    ];

    public function auditLabel(): ?string
    {
        return $this->audit_no . ' - ' . $this->title;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssetAuditItem::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Ringkasan hasil audit.
     *
     * @return array{total:int, checked:int, pending:int, found:int, missing:int, misplaced:int, damaged:int, extra:int, percent:int}
     */
    public function summary(): array
    {
        $row = $this->items()
            ->selectRaw('SUM(in_scope = 1) AS total')
            ->selectRaw('SUM(in_scope = 1 AND result IS NOT NULL) AS checked')
            ->selectRaw('SUM(in_scope = 1 AND result IS NULL) AS pending')
            // found + missing (+ pending) = total; aset di luar daftar dihitung terpisah.
            ->selectRaw("SUM(in_scope = 1 AND result = 'found') AS found")
            ->selectRaw("SUM(in_scope = 1 AND result = 'missing') AS missing")
            ->selectRaw("SUM(in_scope = 1 AND result = 'found' AND location_mismatch = 1) AS misplaced")
            ->selectRaw("SUM(result = 'found' AND `condition` IN ('minor_damage','major_damage')) AS damaged")
            ->selectRaw('SUM(in_scope = 0) AS extra')
            ->first();

        $s = [];

        foreach (['total', 'checked', 'pending', 'found', 'missing', 'misplaced', 'damaged', 'extra'] as $k) {
            $s[$k] = (int) ($row->{$k} ?? 0);
        }

        $s['percent'] = $s['total'] > 0 ? (int) floor($s['checked'] / $s['total'] * 100) : 0;

        return $s;
    }
}
