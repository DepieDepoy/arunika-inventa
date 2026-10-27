<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorageAddonRate extends Model
{
    protected $fillable = [
        'name',
        'unit_bytes',
        'price_monthly',
        'price_yearly',
        'max_units',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'unit_bytes' => 'integer',
            'price_monthly' => 'integer',
            'price_yearly' => 'integer',
            'max_units' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
