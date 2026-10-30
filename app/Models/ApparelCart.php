<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApparelCart extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['items' => 'array'];
}
