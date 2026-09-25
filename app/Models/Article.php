<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Article extends Model
{

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected $fillable = [
        'title',
        'category_id',
        'author_id',
        'issue_id',
        'summary',
        'content',
        'featured_image_url',
        'article_featured_image_url',
        'access_type',
        'single_article_price',
        'status',
        'published_at',
        'buy_button_link',
        'left_image_title'
    ];

    /* Relationships */

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function issue()
    {
        return $this->belongsTo(MagazineIssue::class, 'issue_id');
    }

    public function blocks()
    {
        return $this->hasMany(ArticleBlock::class)->orderBy('block_order');
    }

    public function galleryImages()
    {
        return $this->hasMany(ArticleGalleryImage::class);
    }

    public function purchases()
    {
        return $this->hasMany(ArticlePurchase::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function scopeOrderByIssueTitleDate(Builder $query): Builder
    {
        return $query
            ->orderByRaw("CAST(REGEXP_SUBSTR(title, '[12][0-9]{3}') AS UNSIGNED) DESC")
            ->orderByRaw("
                CASE
                    WHEN LOWER(title) REGEXP '(^|[^a-z])dec(ember)?([^a-z]|$)' THEN 12
                    WHEN LOWER(title) REGEXP '(^|[^a-z])nov(ember)?([^a-z]|$)' THEN 11
                    WHEN LOWER(title) REGEXP '(^|[^a-z])oct(ober)?([^a-z]|$)' THEN 10
                    WHEN LOWER(title) REGEXP '(^|[^a-z])sep(t|tember)?([^a-z]|$)' THEN 9
                    WHEN LOWER(title) REGEXP '(^|[^a-z])aug(ust)?([^a-z]|$)' THEN 8
                    WHEN LOWER(title) REGEXP '(^|[^a-z])jul(y)?([^a-z]|$)' THEN 7
                    WHEN LOWER(title) REGEXP '(^|[^a-z])jun(e)?([^a-z]|$)' THEN 6
                    WHEN LOWER(title) REGEXP '(^|[^a-z])may([^a-z]|$)' THEN 5
                    WHEN LOWER(title) REGEXP '(^|[^a-z])apr(il)?([^a-z]|$)' THEN 4
                    WHEN LOWER(title) REGEXP '(^|[^a-z])mar(ch)?([^a-z]|$)' THEN 3
                    WHEN LOWER(title) REGEXP '(^|[^a-z])feb(ruary)?([^a-z]|$)' THEN 2
                    WHEN LOWER(title) REGEXP '(^|[^a-z])jan(uary)?([^a-z]|$)' THEN 1
                    ELSE 0
                END DESC
            ")
            ->orderBy('published_at', 'desc')
            ->orderBy('id', 'desc');
    }

    public function getRouteKey()
    {
        return Str::slug($this->title) ?: $this->getKey();
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field) {
            return parent::resolveRouteBinding($value, $field);
        }

        if (is_numeric($value)) {
            return $this->whereKey($value)->first();
        }

        return $this->newQuery()
            ->get()
            ->first(fn (self $article) => $article->getRouteKey() === $value);
    }


}
