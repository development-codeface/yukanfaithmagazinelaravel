<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApparelBanner extends Model
{
    protected $fillable = ['title', 'image', 'link', 'status', 'sort_order'];
    protected $casts = ['status' => 'boolean', 'sort_order' => 'integer'];

    public function scopeActive($query)
    {
        return $query->where('status', true)->orderBy('sort_order')->latest('id');
    }
}
