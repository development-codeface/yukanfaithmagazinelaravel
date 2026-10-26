<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MagazineIssue extends Model
{
    protected $table = 'magazine_issues';

    protected $fillable = [
        'title',
        'price',
        'cover_media_id',
        'issue_date',
        'description',
        'pdf_url',
        'published_at',
    ];

    protected $casts = ['issue_date' => 'date', 'published_at' => 'datetime'];

    public function scopeAccessibleTo($query, User $user)
    {
        $subscriptions = $user->subscriptions()->with('plan')->where('status', 'active')
            ->where('payment_status', 1)->where('start_date', '<=', now())
            ->where('end_date', '>=', now())->get();
        return $query->where(function ($query) use ($user, $subscriptions) {
            $query->whereHas('purchases', fn ($q) => $q->where('user_id', $user->id));
            foreach ($subscriptions as $subscription) {
                [$start, $end] = $subscription->magazinePeriod();
                $query->orWhere(function ($q) use ($start, $end) {
                    $q->where(function ($dates) use ($start, $end) {
                        $dates->whereDate('issue_date', '>=', $start->toDateString())
                            ->whereDate('issue_date', '<=', $end->toDateString());
                    })
                        ->orWhere(function ($fallback) use ($start, $end) {
                            $fallback->whereNull('issue_date')->whereBetween('published_at', [$start, $end]);
                        });
                });
            }
        });
    }

    /* ================= Relationships ================= */

    public function purchases() { return $this->hasMany(MagazinePurchase::class); }

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
