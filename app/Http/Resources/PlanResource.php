<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $durationValue = $this->duration ?? $this->duration_days;
        $durationType = $this->duration_type ?? 'days';
        $currentFormats = $this->resource->getAttribute('current_formats');
        $isCurrent = fn ($type) => $currentFormats !== null
            ? in_array($type, $currentFormats, true)
            : (bool) $request->user()?->hasCurrentPackage($this->id, $type);

        return [
            'id' => $this->id,
            'is_current' => collect(array_keys($this->resource->prices()))->contains(fn ($type) => $isCurrent($type)),
            'print_enabled' => (bool) $this->print_enabled,
            'requires_delivery_address' => (bool) $this->print_enabled,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'support_amount' => $this->support_amount ?? '0.00',
            'support_optional' => (float) $this->support_amount > 0,
            'prices' => $this->resource->prices(),
            'available_types' => array_keys($this->resource->prices()),
            'formats' => collect($this->resource->prices())->map(fn ($price, $type) => [
                'type' => $type, 'label' => ['digital' => 'Digital', 'print' => 'Print', 'both' => 'Digital + Print'][$type], 'price' => $price,
                'includes_digital' => $type !== 'print',
                'is_current' => $isCurrent($type),
                'title' => $this->resource->formatContent($type)['title'],
                'description' => $this->resource->formatContent($type)['description'],
                'magazine_access' => $type !== 'print' ? 'all_magazines_during_subscription' : 'none',
                'includes_print' => $type !== 'digital',
                'requires_delivery_address' => $type !== 'digital',
            ])->values(),
            'is_trial' => $this->resource->isTrial(),
            'purchasable' => !$this->resource->isTrial() || (bool) $this->trial_conversion_plan_id,
            'trial_days' => $this->resource->isTrial() ? 7 : null,
            'trial_conversion_plan_id' => $this->trial_conversion_plan_id,
            'trial_conversion' => $this->resource->isTrial() && $this->resource->trialConversionPlan ? [
                'plan_id' => $this->resource->trialConversionPlan->id,
                'name' => $this->resource->trialConversionPlan->name,
                'delivery_type' => 'digital',
                'annual_amount' => $this->resource->trialConversionPlan->digital_price,
                'currency' => strtoupper(config('services.stripe.currency', 'inr')),
                'auto_renew' => false,
            ] : null,
            'trial_checkout_endpoint' => $this->resource->isTrial() ? url('/api/user/trial/'.$this->id.'/checkout') : null,
            'duration' => [
                'value' => $durationValue ? (int) $durationValue : null,
                'type' => $durationType,
                'days' => $this->duration_days ? (int) $this->duration_days : $this->durationInDays($durationValue, $durationType),
                'label' => $this->durationLabel($durationValue, $durationType),
            ],
            'features' => $this->featuresList(),
            'is_active' => $this->isActive(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function durationInDays($durationValue, ?string $durationType): ?int
    {
        if (!$durationValue) {
            return null;
        }

        return match ($durationType) {
            'years' => (int) $durationValue * 365,
            'months' => (int) $durationValue * 30,
            default => (int) $durationValue,
        };
    }

    private function durationLabel($durationValue, ?string $durationType): ?string
    {
        if (!$durationValue) {
            return null;
        }

        $value = (int) $durationValue;
        $type = rtrim($durationType ?: 'days', 's');

        return $value.' '.$type.($value === 1 ? '' : 's');
    }

    private function featuresList(): array
    {
        if (empty($this->features)) {
            return [];
        }

        $decoded = json_decode($this->features, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values($decoded);
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $this->features))));
    }

    private function isActive(): bool
    {
        if (array_key_exists('status', $this->resource->getAttributes())) {
            return (int) $this->status === 1;
        }

        if (array_key_exists('is_active', $this->resource->getAttributes())) {
            return (bool) $this->is_active;
        }

        return true;
    }
}
