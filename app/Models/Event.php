<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'image',
        'button_link',
        'status'
        ];

}
