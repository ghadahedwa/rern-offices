<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\CarbonInterface;

/**
 * عطلة رسمية تقرّرها الدولة — على مستوى الجمهورية دائماً.
 *
 * انظر الهجرة لسبب كونها جدولاً لا استنتاجاً (قرار الترحيل لا قاعدة له).
 */
class OfficialHoliday extends Model
{
    use HasFactory;

    protected $table = 'official_holidays';

    protected $fillable = ['name', 'starts_on', 'ends_on'];

    protected $casts = [
        // ⚠️ 'date' لا 'datetime': يومٌ كتبه المستخدم بلا دلالة توقيت،
        //    وتحويله بتوقيت العرض قد ينقله يوماً كاملاً (قاعدة التوقيت في CLAUDE.md).
        'starts_on' => 'date',
        'ends_on'   => 'date',
    ];

    /** العطلات المتقاطعة مع المدى — تقاطع مدىً بمدى لا احتواء. */
    public function scopeOverlapping(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query->whereDate('starts_on', '<=', $to->toDateString())
            ->whereDate('ends_on', '>=', $from->toDateString());
    }

    /** ترتيب العرض: الأقدم أولاً. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('starts_on')->orderBy('id');
    }

    /** أيام هذه العطلة: 'Y-m-d' => الاسم. */
    public function dates(): array
    {
        $dates = [];

        // ⚠️ `$day = $day->addDay()` لا `$day->addDay()`: المشروع على CarbonImmutable
        //    (Date::use في AppServiceProvider)، والإضافة تُرجع نسخة ولا تمسّ الأصل —
        //    فالصيغة المعتادة تُبقي الشرط صادقاً أبداً: حلقة لا نهائية.
        for ($day = $this->starts_on; $day->lte($this->ends_on); $day = $day->addDay()) {
            $dates[$day->toDateString()] = $this->name;
        }

        return $dates;
    }
}
