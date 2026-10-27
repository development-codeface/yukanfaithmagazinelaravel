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
        'print_enabled' => 'boolean',
        'delivery_address' => 'array',
        'print_acknowledged_at' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'payment_status' => 'boolean',
        'amount_paid' => 'decimal:2',
    ];

    protected $fillable = [
        'delivery_type',
        'print_enabled',
        'delivery_address',
        'print_acknowledged_at',
        'user_id',
        'plan_id',
        'start_date',
        'end_date',
        'status',
        'payment_status',
        'amount_paid',
        'transaction_id',
    ];

    public function magazinePeriod(): array
    {
        return [$this->start_date->copy()->startOfDay(), $this->end_date->copy()->endOfDay()];
    }

    public function includesDigital(): bool
    {
        return ($this->delivery_type ?? 'digital') !== 'print';
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function plan()
    {
        return $this->belongsTo(\App\Models\Plan::class, 'plan_id');
    }
    
}
