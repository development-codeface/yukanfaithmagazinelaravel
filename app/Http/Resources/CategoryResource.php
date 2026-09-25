<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->category_name,
            'description' => $this->description,
            'category_subtitle' => $this->category_subtitle,
            'subtitle_description' => $this->subtitle_description,
            'parent_category_id' => $this->parent_category,
            'sub_categories' => $this->whenLoaded('subcategories', function () {
                return $this->subcategories->map(function ($subCategory) {
                    return [
                        'id' => $subCategory->id,
                        'name' => $subCategory->category_name,
                        'description' => $subCategory->description,
                        'category_subtitle' => $subCategory->category_subtitle,
                        'subtitle_description' => $subCategory->subtitle_description,
                        'parent_category_id' => $subCategory->parent_category,
                        'banner_image' => $subCategory->banner_image ? asset($subCategory->banner_image) : null,
                    ];
                })->values();
            }),
            'banner_image' => $this->banner_image ? asset($this->banner_image) : null,
            'banner_sliders' => $this->whenLoaded('bannerSliders', function () {
                return $this->bannerSliders->map(function ($slider) {
                    return [
                        'id' => $slider->id,
                        'image' => $slider->image ? asset($slider->image) : null,
                        'link' => $slider->link,
                    ];
                })->values();
            }),
            'articles' => $this->whenLoaded('publishedArticles', function () {
                return ArticleResource::collection($this->publishedArticles);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
