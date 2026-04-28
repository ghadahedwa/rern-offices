<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Governorate extends Model
{
    protected $fillable = ['name', 'latitude', 'longitude'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
