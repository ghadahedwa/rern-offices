<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatType extends Model
{
    protected $fillable = ['name', 'period', 'value_type', 'group_key', 'order'];

    public function stats(): HasMany
    {
        return $this->hasMany(OfficeStat::class);
    }
}
