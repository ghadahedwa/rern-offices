<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Office extends Model
{
    protected $fillable = [
        'governorate_id', 'parent_office_id',
        'name', 'established_at', 'visited_at',
        'type_id', 'location_description_id', 'work_system_id',
        'address', 'google_maps_link', 'floors_description',
        'connection_type_id', 'working_hours_id', 'avg_daily_transactions',
        'contractual_status_id', 'structural_condition_id',
        'cleanliness_rating', 'archive_rating',
        'work_schedule_commitment', 'citizen_treatment_commitment',
        'microfilm_option_id', 'disabilities_access_id', 'fire_safety_id',
        'document_photocopying_service_id', 'buffet_service_id', 'cleanliness_contract_id',
        'office_area', 'district_court', 'Braille_sign_device', 'queue_management_system', 'surveillance_cameras',
        'payment_machine_count', 'computers_count', 'monitors_count', 'scanners_count', 'printers_count', 'fingerprints_count',
        'negatives_and_solutions', 'development_proposals',
        'windows_count', 'office_needs', 'air_conditioners_count', 'ups_count',
        'debt_amount',
        'working_days', 'mechanization_at',
        'electricity_meter_type', 'electricity_meter_debt',
        'water_meter_type', 'water_meter_debt',
    ];

    protected $casts = [
        'established_at'  => 'date',
        'visited_at'      => 'date',
        'mechanization_at' => 'date',
    ];

    public const CLEANLINESS_RATINGS = [
        'good'    => 'جيدة',
        'average' => 'متوسطة',
        'bad'     => 'سيئة',
    ];

    public const ARCHIVE_RATINGS = [
        'excellent' => 'ممتازة',
        'good'      => 'جيدة',
        'average'   => 'متوسطة',
        'bad'       => 'سيئة',
        'none'      => 'لا يوجد غرفة حفظ',
    ];

    public const COMMITMENT_RATINGS = [
        'excellent' => 'ممتازة',
        'good'      => 'جيدة',
        'average'   => 'متوسطة',
        'bad'       => 'سيئة',
    ];

    

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function structuralCondition(): BelongsTo
    {
        return $this->belongsTo(StructuralCondition::class, 'structural_condition_id');
    }

    public function officeType(): BelongsTo
    {
        return $this->belongsTo(OfficeType::class, 'type_id');
    }

    public function locationDescription(): BelongsTo
    {
        return $this->belongsTo(LocationDescription::class, 'location_description_id');
    }

    public function workSystem(): BelongsTo
    {
        return $this->belongsTo(WorkSystem::class, 'work_system_id');
    }

    public function connectionType(): BelongsTo
    {
        return $this->belongsTo(ConnectionType::class, 'connection_type_id');
    }

    public function workingHour(): BelongsTo
    {
        return $this->belongsTo(WorkingHour::class, 'working_hours_id');
    }

    public function contractualStatus(): BelongsTo
    {
        return $this->belongsTo(ContractualStatus::class, 'contractual_status_id');
    }

    public function MicrofilmOption(): BelongsTo
    {
        return $this->belongsTo(MicrofilmOption::class, 'microfilm_option_id');
    }

    public function DisabilitieAccess(): BelongsTo
    {
        return $this->belongsTo(DisabilitieAccess::class, 'disabilitie_access_id');
    }

    public function FireSafety(): BelongsTo
    {
        return $this->belongsTo(FireSafety::class, 'fire_safety_id');
    }

    public function DocumentPhotocopyingService(): BelongsTo
    {
        return $this->belongsTo(DocumentPhotocopyingService::class, 'document_photocopying_service_id');
    }

    public function BuffetService(): BelongsTo
    {
        return $this->belongsTo(BuffetService::class, 'buffet_service_id');
    }

    public function CleanlinessContract(): BelongsTo
    {
        return $this->belongsTo(CleanlinessContract::class, 'cleanliness_contract_id');
    }

    public function parentOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'parent_office_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Office::class, 'parent_office_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(OfficeMedia::class);
    }

    public function brokenDevices(): HasMany
    {
        return $this->hasMany(OfficeBrokenDevice::class);
    }

    public function statistics(): HasMany
    {
        return $this->hasMany(OfficeStat::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Office $office) {
            $office->media->each(function ($media) {
                Storage::disk('public')->delete($media->path);
            });
            $office->media()->delete();
        });
    }
}
