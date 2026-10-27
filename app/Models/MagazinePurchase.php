<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MagazinePurchase extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['delivery_address' => 'array', 'print_acknowledged_at' => 'datetime', 'amount_paid' => 'decimal:2'];

    public function magazine() { return $this->belongsTo(MagazineIssue::class, 'magazine_issue_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
