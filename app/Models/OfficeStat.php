<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeStat extends Model
{
    protected $table = 'office_statistics';

    protected $fillable = ['office_id', 'stat_type', 'year', 'month', 'value'];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
