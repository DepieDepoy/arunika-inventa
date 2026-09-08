<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Company;
use App\Models\Role;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User Model
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $phone
 * @property string $email
 * @property string|null $photo
 * @property string $password
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'company_id',
        'name',
        'nik',
        'phone',
        'email',
        'photo',
        'password',
        'must_change_password',
        'status',
        'last_login_at',
        'last_login_ip',
        'role_id',
    ];

    /**
     * Attribute casting.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',    
        ];
    }

    /**
     * Relasi ke perusahaan.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission(string $permission): bool
    {
        // Administrator memiliki seluruh permission
        if ($this->role && $this->role->role_code === 'administrator') {
            return true;
        }

        // Role tidak aktif = tidak memiliki permission
        if (!$this->role || $this->role->status != 1) {
            return false;
        }

        return $this->role
            ->permissions()
            ->where('permission_code', $permission)
            ->exists();
    }
    
}