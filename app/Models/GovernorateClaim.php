<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernorateClaim extends Model
{
    protected $fillable = ['governorate_id', 'year', 'month', 'value'];

    protected $casts = [
        'year'  => 'integer',
        'month' => 'integer',
        'value' => 'decimal:2',
    ];

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }
}
