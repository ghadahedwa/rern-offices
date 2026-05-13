<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeBrokenDevice extends Model
{
    protected $fillable = ['office_id', 'device_type_id', 'count'];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class);
    }
}
