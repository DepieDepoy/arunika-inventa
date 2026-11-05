<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tarif add-on per komponen (storage / asset / user).
 * Harga per unit; diatur langsung dari database.
 */
class AddonRate extends Model
{
    public const TYPES = ['storage', 'asset', 'user'];

    protected $fillable = [
        'type',
        'name',
        'unit_value',
        'unit_label',
        'price_monthly',
        'price_yearly',
        'max_units',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'unit_value' => 'integer',
            'price_monthly' => 'integer',
            'price_yearly' => 'integer',
            'max_units' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
