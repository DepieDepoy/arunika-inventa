<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportError extends Model
{
    protected $fillable = [
        'import_history_id',
        'row_number',
        'data',
        'error_message',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function importHistory(): BelongsTo
    {
        return $this->belongsTo(
            ImportHistory::class
        );
    }
}