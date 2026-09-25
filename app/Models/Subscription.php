<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Subscription extends Model
{
    use Notifiable, HasFactory;

    public $table = 'subscriptions';
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'payment_status' => 'boolean',
        'amount_paid' => 'decimal:2',
    ];

    protected $fillable = [
        'user_id',
        'plan_id',
        'start_date',
        'end_date',
        'status',
        'payment_status',
        'amount_paid',
        'transaction_id',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function plan()
    {
        return $this->belongsTo(\App\Models\Plan::class, 'plan_id');
    }
    
}
