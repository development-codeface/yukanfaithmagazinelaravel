<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApparelProductVariant extends Model
{
    protected $fillable = [
        'apparel_product_id',
        'option_type',
        'option_value',
        'color_name',
        'color_hex',
        'size',
        'sku',
        'price',
        'stock',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(ApparelProduct::class, 'apparel_product_id');
    }

    public function images()
    {
        return $this->hasMany(ApparelProductImage::class)->orderBy('sort_order');
    }

    public function optionValues()
    {
        return $this->belongsToMany(
            ApparelProductOptionValue::class,
            'apparel_product_variant_values',
            'apparel_product_variant_id',
            'apparel_product_option_value_id'
        )->with('option')->withTimestamps();
    }
}
