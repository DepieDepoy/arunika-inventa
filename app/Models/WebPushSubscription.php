<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu browser yang mengizinkan notifikasi Vasetra (Web Push).
 */
class WebPushSubscription extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'endpoint',
        'endpoint_hash',
        'public_key',
        'auth_token',
        'content_encoding',
        'user_agent',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    protected $hidden = ['public_key', 'auth_token'];

    public static function hashEndpoint(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
