<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrialEnrollment extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['format_prices' => 'array', 'annual_amount' => 'decimal:2', 'cancel_requested' => 'boolean', 'trial_ends_at' => 'datetime', 'ends_at' => 'datetime'];
}
