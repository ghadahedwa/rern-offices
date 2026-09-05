<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\CarbonInterface;

/**
 * يومُ استثناءٍ في سجل الحضور (غياب · إجازة) — **الحضور لا يُخزَّن بل يُشتق**.
 *
 * ⚠️ لا صفّ هنا معناه «حضر» — ما دام اليوم يوم عمل داخل مدة خدمة المدخل.
 *    انظر `App\Support\WorkingDays` لمعادلة الاشتقاق.
 */
class AttendanceDay extends Model
{
    use HasFactory;

    protected $table = 'attendance_days';

    protected $fillable = ['attendable_type', 'attendable_id', 'date', 'status_id', 'recorded_by'];

    protected $casts = [
        // ⚠️ 'date' لا 'datetime': يوم كتبه المستخدم، لا لحظة زمنية تُحوَّل بتوقيت العرض.
        'date' => 'date',
    ];

    public function attendable(): MorphTo
    {
        return $this->morphTo();
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(AttendanceStatus::class, 'status_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeBetween(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString());
    }

    public function scopeForOperator(Builder $query, DataEntryOperator|int $operator): Builder
    {
        return $query->where('attendable_type', DataEntryOperator::class)
            ->where('attendable_id', $operator instanceof DataEntryOperator ? $operator->getKey() : $operator);
    }
}
