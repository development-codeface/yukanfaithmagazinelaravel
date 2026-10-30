<?php

namespace App\Support;

class ApparelMoney
{
    public static function prefix(?string $currency = null): string
    {
        $currency = strtoupper($currency ?? config('services.stripe.currency', 'inr'));
        return $currency === 'USD' ? '$' : $currency.' ';
    }
}
