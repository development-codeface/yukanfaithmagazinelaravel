<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApparelProductOptionValue extends Model
{
    protected $fillable = [
        'apparel_product_option_id',
        'value',
        'color_hex',
        'sort_order',
    ];

    public function option()
    {
        return $this->belongsTo(ApparelProductOption::class, 'apparel_product_option_id');
    }

    public function variants()
    {
        return $this->belongsToMany(
            ApparelProductVariant::class,
            'apparel_product_variant_values',
            'apparel_product_option_value_id',
            'apparel_product_variant_id'
        );
    }
}
