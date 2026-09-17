<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * «وصل الكشف» لعاملٍ في مقرٍّ عن شهر — انظر هجرته لسبب وجوده ولسبب المقر في مفتاحه.
 */
class AttendanceReview extends Model
{
    protected $table = 'attendance_reviews';

    protected $fillable = ['attendable_type', 'attendable_id', 'office_id', 'month', 'reviewed_by'];

    protected $casts = [
        // أول يوم في الشهر — يومٌ لا لحظة، فلا تحويل توقيت.
        'month' => 'date',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeForContractor(Builder $query, Contractor|int $contractor): Builder
    {
        return $query->where('attendable_type', Contractor::class)
            ->where('attendable_id', $contractor instanceof Contractor ? $contractor->getKey() : $contractor);
    }
}
