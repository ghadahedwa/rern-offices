<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GovernorateClaim extends Model
{
    use LogsActivity;

    protected $fillable = ['governorate_id', 'year', 'month', 'value'];

    protected $casts = [
        'year'  => 'integer',
        'month' => 'integer',
        'value' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'إضافة محصل',
                'updated' => 'تعديل محصل',
                'deleted' => 'حذف محصل',
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
