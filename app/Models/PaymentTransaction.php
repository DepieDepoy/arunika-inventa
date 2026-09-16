<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'company_id',
        'subscription_id',
        'plan_id',
        'order_id',
        'midtrans_transaction_id',
        'payment_type',
        'transaction_status',
        'fraud_status',
        'gross_amount',
        'va_number',
        'bank',
        'qr_string',
        'payment_url',
        'transaction_time',
        'settlement_time',
        'expiry_time',
        'midtrans_response',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'transaction_time' => 'datetime',
            'settlement_time' => 'datetime',
            'expiry_time' => 'datetime',
            'midtrans_response' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isPending(): bool
    {
        return $this->transaction_status === 'pending';
    }

    public function isPaid(): bool
    {
        return in_array($this->transaction_status, [
            'settlement',
            'capture',
            'paid',
        ], true);
    }

    public function isFailed(): bool
    {
        return in_array($this->transaction_status, [
            'deny',
            'cancel',
            'expire',
            'failure',
        ], true);
    }
}