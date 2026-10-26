<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApparelOrderItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['price' => 'decimal:2'];
}
