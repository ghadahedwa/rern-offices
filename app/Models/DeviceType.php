<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceType extends Model
{
    protected $fillable = ['name'];

    public function brokenDevices(): HasMany
    {
        return $this->hasMany(OfficeBrokenDevice::class);
    }
}
