<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Permintaan hapus akun / data perusahaan.
 */
class AccountDeletionRequest extends Model
{
    public const SCOPES = ['user' => 'Akun pengguna', 'company' => 'Seluruh perusahaan'];
    public const SOURCES = ['app' => 'Aplikasi mobile', 'web' => 'Dashboard web', 'form' => 'Formulir publik'];
    public const STATUSES = ['pending' => 'Menunggu', 'done' => 'Selesai', 'rejected' => 'Ditolak'];

    protected $fillable = [
        'company_id', 'user_id', 'name', 'email', 'scope', 'source', 'reason',
        'status', 'processed_by', 'processed_at', 'note', 'ip',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function processor()
    {
        return $this->belongsTo(BackofficeAdmin::class, 'processed_by');
    }
}
