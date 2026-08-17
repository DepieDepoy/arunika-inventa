<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Company Model
 *
 * @property int $id
 * @property string $company_name
 * @property string $company_code
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Company extends Model
{
    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'company_name',
        'company_code',
        'status',
        'subscription_plan',
        'subscription_status',
        'started_at',
        'expired_at',
    ];

    /**
     * Relasi ke user.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}