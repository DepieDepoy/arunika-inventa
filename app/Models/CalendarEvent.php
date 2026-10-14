<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarEvent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'created_by',
        'title',
        'description',
        'location',
        'event_type',
        'start_at',
        'end_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'calendar_event_attendees',
            'calendar_event_id',
            'user_id'
        )->withPivot([
            'invited_by',
            'response_status',
            'responded_at',
        ])->withTimestamps();
    }

    public function scopeForCompany(
        Builder $query,
        int $companyId
    ): Builder {
        return $query->where('company_id', $companyId);
    }
}