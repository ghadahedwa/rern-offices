<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GovernorateCancelledDemand extends Model
{
    use LogsActivity;

    protected $fillable = ['governorate_id', 'year', 'month', 'amount', 'reason'];

    protected $casts = [
        'year'   => 'integer',
        'month'  => 'integer',
        'amount' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'إضافة مطالبة ملغاة',
                'updated' => 'تعديل مطالبة ملغاة',
                'deleted' => 'حذف مطالبة ملغاة',
                default   => $eventName,
            });
    }

    /** تخزين المحافظة في خصائص السجل لفلترة النطاق (يشمل سجلات الحذف) */
    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->properties = ($activity->properties ?? collect())
            ->put('governorate_id', $this->governorate_id);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }
}
