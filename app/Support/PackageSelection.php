<?php

namespace App\Support;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PackageSelection
{
    public static function forRequest(Request $request, Plan $plan): Plan
    {
        if ($plan->isTrial()) {
            throw ValidationException::withMessages(['plan' => 'The complimentary trial is granted once when an account is created.']);
        }
        $request->validate(['delivery_type' => 'nullable|in:digital,print,both']);
        $prices = $plan->prices();
        $type = $request->input('delivery_type') ?: (isset($prices['digital']) ? 'digital' : (count($prices) === 1 ? array_key_first($prices) : null));
        if (!$type || !array_key_exists($type, $prices)) {
            throw ValidationException::withMessages(['delivery_type' => 'Choose an available package format.']);
        }
        return self::selected($plan, $type, (float) $prices[$type]);
    }

    public static function selected(Plan $plan, string $type, float $price): Plan
    {
        $selected = clone $plan;
        $selected->price = $price;
        $selected->print_enabled = in_array($type, ['print', 'both'], true);
        $selected->setAttribute('selected_delivery_type', $type);
        return $selected;
    }

    public static function fromMetadata(Plan $plan, array $metadata, float $amount): Plan
    {
        $type = $metadata['delivery_type'] ?? (($metadata['print_enabled'] ?? '0') === '1' ? 'both' : 'digital');
        if (!in_array($type, ['digital', 'print', 'both'], true)) {
            throw ValidationException::withMessages(['delivery_type' => 'Invalid payment package format.']);
        }
        return self::selected($plan, $type, $amount);
    }
}
