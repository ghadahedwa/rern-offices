<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleStat extends Model
{
    protected $table = 'vehicle_statistics';

    protected $fillable = ['vehicle_id', 'stat_type_id', 'year', 'month', 'value'];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function statType(): BelongsTo
    {
        return $this->belongsTo(StatType::class);
    }
}
