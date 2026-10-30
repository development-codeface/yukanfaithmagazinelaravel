<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApparelOrder extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['total' => 'decimal:2', 'api_checkout' => 'boolean', 'paid_at' => 'datetime'];

    public function items()
    {
        return $this->hasMany(ApparelOrderItem::class);
    }
}
