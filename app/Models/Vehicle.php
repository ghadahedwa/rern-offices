<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    public const STATUSES = [
        'working'     => 'تعمل',
        'maintenance' => 'متوقفة للصيانة',
        'stopped'     => 'متوقفة',
    ];

    protected $fillable = [
        'governorate_id', 'type_id', 'work_system_id', 'working_hours_id', 'brand_id',
        'name', 'license_plate', 'manufacture_year', 'chassis_number',
        'license_expiry_date', 'status', 'overnight_address', 'storage_room_location', 'notes',
    ];

    protected $casts = [
        'license_expiry_date' => 'date',
    ];

    public function governorate(): BelongsTo  { return $this->belongsTo(Governorate::class); }
    public function type(): BelongsTo         { return $this->belongsTo(VehicleType::class); }
    public function workSystem(): BelongsTo   { return $this->belongsTo(VehicleWorkSystem::class); }
    public function workingHour(): BelongsTo  { return $this->belongsTo(VehicleWorkingHour::class); }
    public function brand(): BelongsTo        { return $this->belongsTo(VehicleBrand::class); }
    public function locations(): HasMany      { return $this->hasMany(VehicleLocation::class); }
}
