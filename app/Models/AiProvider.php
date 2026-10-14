<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProvider extends Model
{
    protected $fillable = [
        'provider_code',
        'provider_name',
        'api_base_url',
        'model',
        'api_key',
        'is_active',
        'priority',
        'settings',
    ];

    protected $casts = [
        /*
         * Laravel akan mengenkripsi API key ketika disimpan
         * dan otomatis decrypt ketika dibaca.
         */
        'api_key' => 'encrypted',

        'is_active' => 'boolean',

        'settings' => 'array',
    ];

    /**
     * Provider AI yang sedang aktif.
     */
    public function scopeActive($query)
    {
        return $query
            ->where('is_active', true)
            ->orderBy('priority');
    }

    /**
     * Ambil provider aktif pertama.
     */
    public static function active(): ?self
    {
        return static::active()->first();
    }
}