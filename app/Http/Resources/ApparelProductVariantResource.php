<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApparelProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'option_type' => $this->option_type,
            'option_value' => $this->option_value,
            'color_name' => $this->color_name,
            'color_hex' => $this->color_hex,
            'size' => $this->size,
            'sku' => $this->sku,
            'price' => $this->price,
            'stock' => $this->stock,
            'status' => (bool) $this->status,
            'sort_order' => $this->sort_order,
            'options' => $this->whenLoaded('optionValues', fn () => $this->optionValues->map(fn ($value) => [
                'name' => $value->option->name ?? null,
                'type' => $value->option->type ?? null,
                'value' => $value->value,
                'color_hex' => $value->color_hex,
            ])->values()),
            'images' => ApparelProductImageResource::collection($this->whenLoaded('images')),
        ];
    }
}
