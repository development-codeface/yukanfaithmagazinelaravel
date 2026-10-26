<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApparelProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->apparel_category_id,
            'category' => new ApparelCategoryResource($this->whenLoaded('category')),
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'stock' => $this->stock,
            'is_featured' => (bool) $this->is_featured,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'size_guide' => $this->size_guide,
            'product_details' => $this->product_details,
            'care_instructions' => $this->care_instructions,
            'shipping_delivery' => $this->shipping_delivery,
            'return_policy' => $this->return_policy,
            'primary_image' => new ApparelProductImageResource($this->whenLoaded('primaryImage')),
            'images' => ApparelProductImageResource::collection($this->whenLoaded('images')),
            'variants' => ApparelProductVariantResource::collection($this->whenLoaded('variants')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
