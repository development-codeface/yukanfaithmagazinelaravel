<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $table = 'regions';

    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    /**
     * Get the users in this region
     */
    public function users()
    {
        return $this->hasMany(User::class, 'region_id');
    }
}
