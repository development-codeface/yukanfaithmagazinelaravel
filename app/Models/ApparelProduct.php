<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApparelProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'apparel_category_id',
        'name',
        'slug',
        'sku',
        'price',
        'compare_at_price',
        'stock',
        'status',
        'is_featured',
        'short_description',
        'description',
        'size_guide',
        'product_details',
        'care_instructions',
        'shipping_delivery',
        'return_policy',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'is_featured' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ApparelCategory::class, 'apparel_category_id');
    }

    public function images()
    {
        return $this->hasMany(ApparelProductImage::class)
            ->whereNull('apparel_product_variant_id')
            ->orderBy('sort_order');
    }

    public function variants()
    {
        return $this->hasMany(ApparelProductVariant::class)->orderBy('sort_order');
    }

    public function options()
    {
        return $this->hasMany(ApparelProductOption::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ApparelProductImage::class)
            ->whereNull('apparel_product_variant_id')
            ->orderBy('sort_order');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public static function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name) ?: Str::random(8);
        $baseSlug = $slug;
        $counter = 2;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
            ->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }
}
