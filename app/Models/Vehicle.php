<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Vehicle extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'إضافة سيارة',
                'updated' => 'تعديل سيارة',
                'deleted' => 'حذف سيارة',
                default    => $eventName,
            });
    }

    public const STATUSES = [
        'working'     => 'تعمل',
        'maintenance' => 'متوقفة للصيانة',
        'stopped'     => 'متوقفة',
    ];

    protected $fillable = [
        'governorate_id', 'type_id', 'work_system_id', 'working_hours_id', 'brand_id',
        'name', 'license_plate', 'manufacture_year', 'operated_at', 'chassis_number',
        'license_expiry_date', 'status', 'overnight_address', 'storage_room_location', 'notes',
        'driver_name', 'driver_phone', 'notary_name', 'notary_phone', 'reviewer_name', 'reviewer_phone',
        'mobility_bag', 'laptops_count', 'fingerprints_count', 'printers_count',
        'collection_machines_count', 'mifi_count', 'generator_status', 'surveillance_cameras',
        'avg_daily_transactions',
    ];

    protected $casts = [
        'license_expiry_date' => 'date',
        'operated_at'         => 'date',
    ];

    public function governorate(): BelongsTo  { return $this->belongsTo(Governorate::class); }
    public function type(): BelongsTo         { return $this->belongsTo(VehicleType::class); }
    public function workSystem(): BelongsTo   { return $this->belongsTo(VehicleWorkSystem::class); }
    public function workingHour(): BelongsTo  { return $this->belongsTo(VehicleWorkingHour::class); }
    public function brand(): BelongsTo        { return $this->belongsTo(VehicleBrand::class); }
    public function locations(): HasMany      { return $this->hasMany(VehicleLocation::class); }
    public function brokenDevices(): HasMany  { return $this->hasMany(VehicleBrokenDevice::class); }
    public function media(): HasMany          { return $this->hasMany(VehicleMedia::class); }
    public function statistics(): HasMany     { return $this->hasMany(VehicleStat::class); }

    protected static function booted(): void
    {
        static::deleting(function (Vehicle $vehicle) {
            $vehicle->media->each(function ($media) {
                Storage::disk('public')->delete($media->path);
            });
            $vehicle->media()->delete();
        });
    }
}
