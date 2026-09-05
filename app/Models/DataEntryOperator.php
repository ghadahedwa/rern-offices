<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\CarbonInterface;

/**
 * مدخل بيانات لدى الشركة المتعاقدة — يُسكَّن في مقر بمدىً زمني.
 *
 * ⚠️ لا مقر ولا محافظة على هذا الجدول: التسكين تاريخيّ في `data_entry_assignments`
 *    (انظر هجرته). والمحافظة تُشتق من المقر ولا تُحفظ — المقر مصدر الحقيقة.
 */
class DataEntryOperator extends Model
{
    use HasFactory;

    protected $table = 'data_entry_operators';

    protected $fillable = ['name', 'phone', 'notes'];

    public function assignments(): HasMany
    {
        return $this->hasMany(DataEntryAssignment::class, 'operator_id');
    }

    /** التسكين المفتوح — الغائب يعني منتهي الخدمة (أو بانتظار تسكين). */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(DataEntryAssignment::class, 'operator_id')
            ->whereNull('ended_on')
            ->latestOfMany('started_on');
    }

    public function attendanceDays(): HasMany
    {
        return $this->hasMany(AttendanceDay::class, 'attendable_id')
            ->where('attendable_type', static::class);
    }

    /** على رأس العمل اليوم: له تسكين مفتوح. */
    public function scopeInService(Builder $query): Builder
    {
        return $query->whereHas('assignments', fn (Builder $q) => $q->whereNull('ended_on'));
    }

    /** كان على رأس العمل في يومٍ بعينه — للتقارير عن فترات ماضية. */
    public function scopeInServiceOn(Builder $query, CarbonInterface $date): Builder
    {
        return $query->whereHas('assignments', fn (Builder $q) => $q->covering($date));
    }

    /** مَن خدم يوماً واحداً على الأقل داخل المدى — أساس تقارير الفترة. */
    public function scopeServingBetween(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query->whereHas('assignments', fn (Builder $q) => $q->overlapping($from, $to));
    }

    /** مقصور على مقرات محافظاتٍ بعينها — أساس نطاق الرؤية. */
    public function scopeInGovernorates(Builder $query, array $governorateIds): Builder
    {
        return $query->whereHas(
            'assignments',
            fn (Builder $q) => $q->whereHas(
                'office',
                fn (Builder $o) => $o->whereIn('governorate_id', $governorateIds)
            )
        );
    }

    public function isInService(): bool
    {
        return $this->assignments()->whereNull('ended_on')->exists();
    }
}
