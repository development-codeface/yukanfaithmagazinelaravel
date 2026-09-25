<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class banner extends Model
{
    use SoftDeletes, Notifiable, HasFactory;

    public $table = 'banners';
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'image',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
