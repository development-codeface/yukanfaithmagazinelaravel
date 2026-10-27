<?php

namespace App\Support;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class RegistrationTrial
{
    public static function grant(User $user): Subscription
    {
        $defaults = [
            'is_trial' => true, 'price' => 0, 'digital_price' => 0,
            'duration_days' => 7, 'duration' => 7, 'duration_type' => 'days',
            'is_active' => true, 'status' => 1,
        ];
        $defaults = array_intersect_key($defaults, array_flip(Schema::getColumnListing('plans')));
        $plan = Plan::where('is_trial', true)->first() ?? Plan::firstOrCreate(['name' => 'Free Trial'], $defaults);
        $start = now();
        return $user->subscriptions()->firstOrCreate(['plan_id' => $plan->id], [
            'start_date' => $start, 'end_date' => $plan->endsAt($start),
            'status' => 'active', 'payment_status' => 1, 'amount_paid' => 0,
            'delivery_type' => 'digital', 'print_enabled' => false,
        ]);
    }
}
