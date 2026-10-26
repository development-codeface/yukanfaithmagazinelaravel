<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApparelProductOption extends Model
{
    protected $fillable = [
        'apparel_product_id',
        'name',
        'type',
        'sort_order',
    ];

    public function product()
    {
        return $this->belongsTo(ApparelProduct::class, 'apparel_product_id');
    }

    public function values()
    {
        return $this->hasMany(ApparelProductOptionValue::class)->orderBy('sort_order');
    }
}
