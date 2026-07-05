<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleBrokenDevice extends Model
{
    protected $fillable = ['vehicle_id', 'device_type_id', 'count'];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(VehicleDeviceType::class, 'device_type_id');
    }
}
