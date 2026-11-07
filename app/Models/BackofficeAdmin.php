<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Akun pegawai Vasetra untuk backoffice (CMS landing, paket & harga).
 *
 * Terpisah total dari `users` (pelanggan): tabel, guard ("backoffice"), dan
 * halaman login sendiri. Akun pelanggan tidak bisa dipakai di backoffice.
 */
class BackofficeAdmin extends Authenticatable
{
    public const ROLE_SUPER = 'super_admin';
    public const ROLE_EDITOR = 'editor';

    public const ROLES = [
        self::ROLE_SUPER => 'Super Admin',
        self::ROLE_EDITOR => 'Editor',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER;
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst((string) $this->role);
    }

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
