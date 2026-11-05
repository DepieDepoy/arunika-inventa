<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


/**
 * User Model
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $nik
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
class User extends Authenticatable implements MustVerifyEmail
{
    use \App\Models\Concerns\Auditable;

    protected string $auditModule = 'user';

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
        'last_login_device',
        'role_id',
        'email_verified_at',
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

    /**
     * Relasi ke role.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Aset yang menjadi tanggung jawab user.
     */
    public function assets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Asset::class, 'responsible_user_id');
    }

    /**
     * Cek permission user.
     */
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

    /**
     * Apakah user memiliki role Administrator di PT-nya.
     */
    public function isAdministrator(): bool
    {
        return $this->role
            && $this->role->role_code === 'administrator'
            && (int) $this->role->company_id === (int) $this->company_id;
    }

    /**
     * Custom email reset password.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(
            new ResetPasswordNotification($token)
        );
    }

    /**
     * Custom email verification VASETRA.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(
            new VerifyEmailNotification()
        );
    }
}