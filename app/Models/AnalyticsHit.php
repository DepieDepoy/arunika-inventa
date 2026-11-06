<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu kunjungan halaman publik atau event funnel (signup / login).
 */
class AnalyticsHit extends Model
{
    public const UPDATED_AT = null;

    public const PAGE_VIEW = 'page_view';
    public const SIGNUP = 'signup';
    public const LOGIN = 'login';

    public const PAGES = [
        'landing' => 'Landing page',
        'register' => 'Halaman daftar',
        'login' => 'Halaman login',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
