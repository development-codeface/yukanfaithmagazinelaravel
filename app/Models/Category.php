<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'category_name',
        'parent_category',
        'description',
        'banner_image',
        'category_subtitle',
        'subtitle_description',

    ];

    public function bannerSliders()
    {
        return $this->hasMany(BannerSlider::class, 'category_id');
    }

    public function subcategories()
    {
        return $this->hasMany(self::class, 'parent_category');
    }

    public function parentCategory()
    {
        return $this->belongsTo(self::class, 'parent_category');
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class);
    }

    public function scopeVisibleOnFrontend($query)
    {
        return $query->whereRaw(
            "REPLACE(REPLACE(LOWER(TRIM(category_name)), '-', ' '), '_', ' ') NOT IN (?, ?, ?)",
            ['past magazines', 'past magazine', 'magazine']
        );
    }

    public function getRouteKey()
    {
        return Str::slug($this->category_name) ?: $this->getKey();
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
            ->first(fn (self $category) => $category->getRouteKey() === $value);
    }
}
