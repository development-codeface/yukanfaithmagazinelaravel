<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Plan extends Model
{
      use Notifiable, HasFactory;

    public $table = 'plans';
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'name',
        'price',
        'duration_days',
        'duration',
        'duration_type',
        'description',
        'features',
        'is_active',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
