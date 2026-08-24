<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'category_id',
        'sub_category_id',
        'vendor_id',
        'responsible_user_id',

        'asset_code',
        'asset_name',
        'brand',
        'model',
        'serial_number',
        'description',

        'purchase_date',
        'purchase_price',
        'purchase_invoice',

        'depreciation_method',
        'useful_life',
        'residual_value',
        'depreciation_start_date',

        'warranty_start',
        'warranty_end',
        'warranty_note',

        'images',
        'invoice_documents',

        'location',
        'status',

        'qr_token',
        'qr_generated_at',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'residual_value' => 'decimal:2',
        'depreciation_start_date' => 'date',
        'warranty_start' => 'date',
        'warranty_end' => 'date',
        'qr_generated_at' => 'datetime',
        'images' => 'array',
        'invoice_documents' => 'array',
    ];

    /**
     * Company
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Sub Category
     */
    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    /**
     * Vendor
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Responsible User
     */
    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }
}