<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticlePurchase extends Model
{
    protected $fillable = [
        'user_id',
        'article_id',
        'amount_paid',
        'payment_status',
        'transaction_id',
        'purchased_at',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'payment_status' => 'boolean',
        'purchased_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
