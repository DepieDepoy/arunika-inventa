<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Catatan audit trail (append-only).
 *
 * Tidak bisa diubah atau dihapus lewat Eloquent agar
 * riwayat tetap utuh sebagai bukti.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'user_id',
        'user_name',
        'event',
        'module',
        'description',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'source',
        'ip_address',
        'user_agent',
        'method',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Audit log tidak dapat diubah.');
        });

        static::deleting(function () {
            throw new LogicException('Audit log tidak dapat dihapus.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
