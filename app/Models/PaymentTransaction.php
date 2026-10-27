<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        // RELATION
        'company_id',
        'subscription_id',
        'plan_id',

        // TRANSACTION
        'order_id',
        'plan_name',
        'billing_cycle',

        // PRICE BREAKDOWN
        'package_price',
        'storage_addon_gb',
        'storage_addon_price',
        'discount_amount',
        'gross_amount',

        // PAYMENT STATUS
        'midtrans_transaction_id',
        'payment_type',
        'transaction_status',
        'fraud_status',

        // PAYMENT DETAILS
        'va_number',
        'bank',
        'qr_string',
        'payment_url',

        // PAYMENT TIMESTAMPS
        'transaction_time',
        'settlement_time',
        'expiry_time',

        // GATEWAY RESPONSE
        'midtrans_response',
    ];

    protected function casts(): array
    {
        return [
            // PRICE
            'package_price'       => 'decimal:2',
            'storage_addon_gb'    => 'decimal:2',
            'storage_addon_price' => 'decimal:2',
            'discount_amount'     => 'decimal:2',
            'gross_amount'        => 'decimal:2',

            // DATETIME
            'transaction_time' => 'datetime',
            'settlement_time'  => 'datetime',
            'expiry_time'      => 'datetime',

            // GATEWAY RESPONSE
            'midtrans_response' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | PAYMENT STATUS HELPERS
    |--------------------------------------------------------------------------
    */

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
            'expired',
            'failure',
            'failed',
        ], true);
    }
}
