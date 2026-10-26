<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApparelProductImage extends Model
{
    protected $fillable = [
        'apparel_product_id',
        'apparel_product_variant_id',
        'image_path',
        'alt_text',
        'sort_order',
    ];

    public function product()
    {
        return $this->belongsTo(ApparelProduct::class, 'apparel_product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ApparelProductVariant::class, 'apparel_product_variant_id');
    }
}
