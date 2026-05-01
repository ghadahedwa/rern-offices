<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeMedia extends Model
{
    protected $table = 'office_media';

    protected $fillable = ['office_id', 'type', 'path'];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
