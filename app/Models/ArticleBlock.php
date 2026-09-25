<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleBlock extends Model
{
    protected $fillable = [
        'article_id',
        'block_type',
        'block_order',
        'block_data',
    ];

    protected $casts = [
        'block_data' => 'array',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
