<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApparelCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image ? asset($this->image) : null,
            'sort_order' => $this->sort_order,
            'products_count' => $this->whenCounted('products'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
