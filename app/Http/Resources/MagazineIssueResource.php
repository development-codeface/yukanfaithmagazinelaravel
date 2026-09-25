<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MagazineIssueResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'issue_date' => $this->issue_date,
            'published_at' => $this->published_at,
            'has_reader' => true,
            'has_pdf' => !empty($this->pdf_url),
            'pdf_url' => $this->absoluteAssetUrl($this->pdf_url),
            'reader_type' => $this->pdf_url ? 'pdf' : 'article',
            'cover_image' => $this->coverMedia?->file_path ? asset($this->coverMedia->file_path) : null,
            'total_articles' => $this->articles?->count(),
            'articles' => ArticleResource::collection($this->whenLoaded('articles')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function absoluteAssetUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }
}
