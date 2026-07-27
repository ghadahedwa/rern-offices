<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuggestionDomain extends Model
{
    protected $fillable = ['key', 'name', 'order'];

    public function topics(): HasMany
    {
        return $this->hasMany(SuggestionTopic::class)->orderBy('order');
    }
}
