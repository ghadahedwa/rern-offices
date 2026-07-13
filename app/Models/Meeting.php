<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Meeting extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'إضافة اجتماع',
                'updated' => 'تعديل اجتماع',
                'deleted' => 'حذف اجتماع',
                default   => $eventName,
            });
    }

    protected $fillable = [
        'date',
        'time',
        'subject',
        'location',
        'concerned_party',
        'concerned_party_title',
        'result',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
