<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleGalleryImage extends Model
{
    protected $fillable = ['article_id', 'image_path'];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
