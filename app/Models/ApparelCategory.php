<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApparelCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'image',
        'description',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(ApparelProduct::class);
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
