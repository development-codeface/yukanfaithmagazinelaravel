<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BannerSlider extends Model
{
     use HasFactory;

    protected $table = 'banner_sliders';

    protected $fillable = [
        'category_id',
        'image',
        'link'
    ];
}
