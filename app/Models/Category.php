<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use \App\Models\Concerns\Auditable;

    protected string $auditModule = 'category';

    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'category_name',
        'category_code',
        'description',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class);
    }
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

}