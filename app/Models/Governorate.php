<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Governorate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'order', 'supervising_counselor', 'latitude', 'longitude'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(GovernorateClaim::class);
    }

    public function demands(): HasMany
    {
        return $this->hasMany(GovernorateDemand::class);
    }

    public function cancelledDemands(): HasMany
    {
        return $this->hasMany(GovernorateCancelledDemand::class);
    }
}
