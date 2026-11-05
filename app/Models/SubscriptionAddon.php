<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Add-on kapasitas (storage / asset / user) dengan masa berlaku sendiri.
 */
class SubscriptionAddon extends Model
{
    use \App\Models\Concerns\Auditable;

    protected string $auditModule = 'subscription';

    public function auditLabel(): ?string
    {
        return 'Add-on ' . $this->type . ' ' . $this->units . ' unit';
    }

    protected $fillable = [
        'company_id',
        'subscription_id',
        'payment_transaction_id',
        'type',
        'units',
        'unit_value',
        'total_value',
        'price',
        'duration_months',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'units' => 'integer',
            'unit_value' => 'integer',
            'total_value' => 'integer',
            'price' => 'decimal:2',
            'duration_months' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Add-on yang berlaku pada tanggal tertentu (default hari ini).
     */
    public function scopeActiveOn(Builder $query, $date = null): Builder
    {
        $date ??= today();

        return $query->where('status', 'active')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }
}
