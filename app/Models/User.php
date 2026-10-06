<?php

namespace App\Models;

use Carbon\Carbon;
use Hash;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
//use Laravel\Passport\HasApiTokens;
use \DateTimeInterface;
use App\Models\Article;
use App\Models\ArticlePurchase;
use App\Models\Subscription;

class User extends Authenticatable
{
    use SoftDeletes, Notifiable, HasFactory;

    public $table = 'users';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $dates = [
        'updated_at',
        'created_at',
        'deleted_at',
        'email_verified_at',
    ];

    protected $fillable = [
        'name',
        'email',
        'stripe_customer_id',
        'region_id',
        'password',
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
        'email_verified_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');

    }

    public function getIsAdminAttribute()
    {
        return $this->roles()->where('roles.id', 1)->exists();

    }

    public function getEmailVerifiedAtAttribute($value)
    {
        return $value ? Carbon::createFromFormat('Y-m-d H:i:s', $value)->format(config('panel.date_format') . ' ' . config('panel.time_format')) : null;

    }

    public function setEmailVerifiedAtAttribute($value)
    {
        $this->attributes['email_verified_at'] = $value ? Carbon::createFromFormat(config('panel.date_format') . ' ' . config('panel.time_format'), $value)->format('Y-m-d H:i:s') : null;

    }

    public function setPasswordAttribute($input)
    {
        if ($input) {
            $this->attributes['password'] = app('hash')->needsRehash($input) ? Hash::make($input) : $input;
        }

    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));

    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);

    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function articlePurchases()
    {
        return $this->hasMany(ArticlePurchase::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('payment_status', 1)
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->latestOfMany();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function hasPurchasedArticle(Article|int $article): bool
    {
        $articleId = $article instanceof Article ? $article->id : $article;

        return $this->articlePurchases()
            ->where('article_id', $articleId)
            ->where('payment_status', 1)
            ->exists();
    }

    public function canReadArticle(Article $article): bool
    {
        if (($article->access_type ?? 'free') === 'free') {
            return true;
        }

        return $this->hasActiveSubscription() || $this->hasPurchasedArticle($article);
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');

    }

    public function getRoleNameAttribute()
    {
        return optional($this->roles->first())->title;
    }

}
