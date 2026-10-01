<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPendingAction extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'action',
        'token',
        'payload',
        'status',
        'expires_at',
        'confirmed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending'
            && $this->expires_at
            && $this->expires_at->isFuture();
    }
}