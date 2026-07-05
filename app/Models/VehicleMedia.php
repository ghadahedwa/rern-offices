<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleMedia extends Model
{
    protected $table = 'vehicle_media';

    protected $fillable = ['vehicle_id', 'type', 'path', 'original_name'];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
