<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApparelOrder extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['total' => 'decimal:2'];

    public function items()
    {
        return $this->hasMany(ApparelOrderItem::class);
    }
}
