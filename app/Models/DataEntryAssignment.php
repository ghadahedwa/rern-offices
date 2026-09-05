<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * تسكين مدخل بيانات في مقر بمدىً زمني — الالتحاق والنقل وإنهاء الخدمة في جدول واحد.
 *
 * ⚠️ **مدة الخدمة تُقرأ من هنا وحدها**، وعليها يقوم حساب الحضور المشتقّ.
 */
class DataEntryAssignment extends Model
{
    use HasFactory;

    /** أسباب إغلاق التسكين — للعرض لا للحساب. */
    public const REASON_TRANSFER = 'transfer';
    public const REASON_LEFT     = 'left';

    protected $table = 'data_entry_assignments';

    protected $fillable = ['operator_id', 'office_id', 'started_on', 'ended_on', 'end_reason'];

    protected $casts = [
        'started_on' => 'date',
        'ended_on'   => 'date',
    ];

    public function operator(): BelongsTo
    {
        return $this->belongsTo(DataEntryOperator::class, 'operator_id');
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    /** التسكين الذي يغطي يوماً بعينه (المفتوح يغطي كل ما بعد بدايته). */
    public function scopeCovering(Builder $query, CarbonInterface $date): Builder
    {
        $day = $date->toDateString();

        return $query->whereDate('started_on', '<=', $day)
            ->where(fn (Builder $q) => $q->whereNull('ended_on')->orWhereDate('ended_on', '>=', $day));
    }

    /** التسكينات المتقاطعة مع مدى — تقاطع لا احتواء. */
    public function scopeOverlapping(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query->whereDate('started_on', '<=', $to->toDateString())
            ->where(fn (Builder $q) => $q->whereNull('ended_on')
                ->orWhereDate('ended_on', '>=', $from->toDateString()));
    }

    public function isOpen(): bool
    {
        return $this->ended_on === null;
    }

    /**
     * هل يتداخل هذا التسكين مع تسكينٍ آخر لنفس المدخل؟
     *
     * ⚠️ حارس **في التطبيق لا في المحرّك**: القيد على مدىً زمني لا على صفّ،
     *    فلا يُعبَّر عنه بـUNIQUE. وبلا هذا الفحص يحمل المدخل مقرّين في يومٍ واحد،
     *    فيُعدّ يومه مرتين في تقرير المحافظة ومرة في كل مقر.
     */
    public function overlapsExisting(): bool
    {
        $start = $this->started_on instanceof CarbonInterface
            ? $this->started_on
            : CarbonImmutable::parse($this->started_on);

        $end = $this->ended_on
            ? ($this->ended_on instanceof CarbonInterface ? $this->ended_on : CarbonImmutable::parse($this->ended_on))
            // تسكين مفتوح يمتدّ بلا نهاية، فأي تسكينٍ يبدأ بعده متداخل معه.
            : CarbonImmutable::parse('9999-12-31');

        return static::query()
            ->where('operator_id', $this->operator_id)
            ->when($this->exists, fn (Builder $q) => $q->whereKeyNot($this->getKey()))
            ->overlapping($start, $end)
            ->exists();
    }
}
