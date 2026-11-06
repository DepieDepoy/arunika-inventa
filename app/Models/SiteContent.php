<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu bagian konten landing page (mis. "hero", "faq"), disimpan sebagai JSON.
 */
class SiteContent extends Model
{
    protected $fillable = [
        'key',
        'locale',
        'value',
        'updated_by',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function editor()
    {
        return $this->belongsTo(BackofficeAdmin::class, 'updated_by');
    }
}
