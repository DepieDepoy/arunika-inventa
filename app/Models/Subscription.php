<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use \App\Models\Concerns\Auditable;

    protected string $auditModule = 'subscription';

    public function auditLabel(): ?string
    {
        return trim(($this->plan?->plan_name ?? 'Plan') . ' (' . $this->billing_cycle . ')');
    }

    
    protected $fillable = [
        'company_id',
        'plan_id',
        'kind',
        'priority',
        'parent_subscription_id',
        'billing_cycle',

        'price',
        'storage_addon_gb',
        'storage_addon_price',

        // STORAGE QUOTA
        'base_storage_bytes',
        'storage_addon_bytes',
        'total_storage_bytes',

        'discount_amount',
        'total_price',

        'start_date',
        'end_date',
        'status',
        'payment_status',
    ];


    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'storage_addon_gb' => 'decimal:2',
            'storage_addon_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_price' => 'decimal:2',

            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->start_date->lte(today())
            && $this->end_date->gte(today());
    }

    public function isExpired(): bool
    {
        return $this->end_date->lt(today());
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(SubscriptionAddon::class);
    }

    /**
     * Untuk upgrade: paket regular yang ditimpa sementara.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'parent_subscription_id');
    }

    public function isUpgrade(): bool
    {
        return $this->kind === 'upgrade';
    }
}
