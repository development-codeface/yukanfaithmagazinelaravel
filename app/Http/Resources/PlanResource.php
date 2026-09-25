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

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
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
