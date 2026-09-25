<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MagazineIssue extends Model
{
    protected $table = 'magazine_issues';

    protected $fillable = [
        'title',
        'cover_media_id',
        'issue_date',
        'description',
        'pdf_url',
        'published_at',
    ];

    /* ================= Relationships ================= */

    // One issue has many articles
    public function articles()
    {
        return $this->hasMany(Article::class, 'issue_id');
    }

    // Optional: cover image
    public function coverMedia()
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }
}