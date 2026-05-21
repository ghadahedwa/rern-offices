<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Governorate extends Model
{
    protected $fillable = ['name', 'order', 'supervising_counselor', 'latitude', 'longitude'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }
}
