<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $accessType = $this->access_type ?? 'free';
        $canRead = $accessType === 'free' || (bool) $request->user()?->canReadArticle($this->resource);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'summary' => $this->summary,
            'content' => $canRead ? $this->content : null,
            'blocks' => $this->whenLoaded('blocks', function () use ($canRead) {
                if (!$canRead) {
                    return null;
                }

                $blocks = $this->blocks->map(function ($block) {
                    $data = $block->block_data ?? [];

                    if (!empty($data['image']) && !filter_var($data['image'], FILTER_VALIDATE_URL)) {
                        $data['image_url'] = asset(ltrim($data['image'], '/'));
                    }

                    return [
                        'id' => $block->id,
                        'type' => $block->block_type,
                        'order' => $block->block_order + ($this->featured_image_url ? 1 : 0),
                        'data' => $data,
                    ];
                });

                if ($this->featured_image_url) {
                    $blocks->prepend([
                        'id' => null,
                        'type' => 'text_image',
                        'order' => 0,
                        'data' => [
                            'content' => null,
                            'caption' => null,
                            'image' => $this->featured_image_url,
                            'image_url' => asset(ltrim($this->featured_image_url, '/')),
                        ],
                    ]);
                }

                return $blocks->values();
            }),
            'featured_image' => ($this->article_featured_image_url ?: $this->featured_image_url)
                ? asset($this->article_featured_image_url ?: $this->featured_image_url)
                : null,
            'left_side_fixed_image' => $this->featured_image_url ? asset($this->featured_image_url) : null,
            'access_type' => $accessType,
            'single_article_price' => $this->single_article_price,
            'can_read' => $canRead,
            'is_locked' => !$canRead,
            'purchase_required' => $accessType === 'paid' && !$canRead,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->category_name,
            ],
            'categories' => $this->whenLoaded('categories', function () {
                return $this->categories->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->category_name,
                ])->values();
            }),
            'author' => [
                'id' => $this->author?->id,
                'name' => $this->author?->name,
            ],
            'issue' => [
                'id' => $this->issue?->id,
                'title' => $this->issue?->title,
            ],
            'gallery_images' => $this->whenLoaded('galleryImages', function () {
                return $this->galleryImages->map(fn ($image) => [
                    'id' => $image->id,
                    'image_path' => $image->image_path,
                    'image_url' => $image->image_path ? asset(ltrim($image->image_path, '/')) : null,
                ])->values();
            }),
            'buy_button_link' => $this->buy_button_link,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
