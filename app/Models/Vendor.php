<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'vendor_code',
        'vendor_name',
        'address',
        'phone',
        'pic_name',
        'email',
        'description',
        'status',
    ];

    /**
     * Company
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Assets
     */
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}