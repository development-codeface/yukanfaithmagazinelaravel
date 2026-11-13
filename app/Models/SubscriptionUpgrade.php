<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionUpgrade extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['format_prices' => 'array', 'delivery_address' => 'array', 'amount_minor' => 'integer',
        'quoted_at' => 'datetime', 'subscription_start' => 'datetime', 'subscription_end' => 'datetime', 'paid_at' => 'datetime', 'applied_at' => 'datetime'];
    public function subscription() { return $this->belongsTo(Subscription::class); }
}
