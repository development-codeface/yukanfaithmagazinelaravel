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
            throw ValidationException::withMessages(['plan' => 'Start the trial through trial checkout to set up your payment method.']);
        }
        $request->validate(['delivery_type' => 'nullable|in:digital,print,both']);
        $prices = $plan->prices();
        $type = $request->input('delivery_type') ?: (isset($prices['digital']) ? 'digital' : (count($prices) === 1 ? array_key_first($prices) : null));
        if (!$type || !array_key_exists($type, $prices)) {
            throw ValidationException::withMessages(['delivery_type' => 'Choose an available package format.']);
        }
        if ($type !== 'print' && $request->user() && \App\Models\TrialEnrollment::where('user_id', $request->user()->id)
            ->where('status', 'trialing')->where('cancel_requested', false)->exists()) {
            throw ValidationException::withMessages(['plan' => 'Cancel your scheduled trial billing before buying another Digital package to avoid two charges.']);
        }
        if ($request->user()?->hasCurrentPackage($plan->id, $type)) {
            throw ValidationException::withMessages(['plan' => 'This is your current package. You can purchase it again after it expires.']);
        }
        $request->validate(['include_support' => 'nullable|boolean']);
        $selected = self::selected($plan, $type, (float) $prices[$type]);
        $selected->setAttribute('selected_support_amount', $request->boolean('include_support') ? (float) ($plan->support_amount ?? 0) : 0);
        return $selected;
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
        $support = (float) ($metadata['support_amount'] ?? 0);
        if ($support < 0 || $support > $amount) {
            throw ValidationException::withMessages(['support_amount' => 'Invalid support payment.']);
        }
        $selected = self::selected($plan, $type, $amount - $support);
        $prices = isset($metadata['format_prices']) ? json_decode($metadata['format_prices'], true) : $plan->prices();
        if (is_array($prices)) $prices[$type] = number_format($amount - $support, 2, '.', '');
        $selected->setAttribute('selected_format_prices', is_array($prices) ? $prices : $plan->prices());
        $selected->setAttribute('selected_support_amount', $support);
        return $selected;
    }
}
