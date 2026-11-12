<?php

namespace App\Models;

use App\Support\Finance\FinanceAccess;
use App\Support\Finance\FinanceCalendar;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ملاحظة على شهرٍ من حساب — أكثر من واحدة للشهر، بتاريخها وكاتبها (قرار العميل).
 *
 * **تتبع قفل شهرها** (قرار المستخدمة): والشهر مفتوح (لم تُحفظ أرقامه) يكتبها صاحب
 * إدخال الحساب (`FinanceAccess::canEnter`) ويعدّلها ويحذفها **كاتبها وحده**؛ ولحظة حفظ الشهر تُقفل معه، فالإضافة
 * والتعديل بـ`finance.edit` والحذف بـ`finance.delete` — ويُسجَّل ذلك في سجلّ الشهر.
 * ⚠️ الحُرّاس هنا والإجراءات تستدعيها — الزرّ المخفي لا يمنع النداء.
 */
class FinanceNote extends Model
{
    public const MAX_LENGTH = 2000;

    protected $fillable = ['finance_account_id', 'finance_unit_id', 'month', 'body', 'created_by'];

    protected $casts = [
        'month' => 'date',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'finance_account_id');
    }

    /** وحدة الشهر — NULL لملاحظات الحسابات الثلاثة وصفحة إجمالي أبنية المحاكم */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(FinanceUnit::class, 'finance_unit_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** ملاحظات صفحة إجمالي أبنية المحاكم — لا وحدة ولا شهرٌ مُدخَل وراءها */
    protected static function isTotalPage(FinanceAccount $account, ?int $unitId): bool
    {
        return $account->isItemized() && $unitId === null;
    }

    /**
     * هل حُفظت أرقام الشهر في دفتره (الوحدة أو الحساب)؟ — ⚠️ نطاقٌ لا مساواة (`'Y-m-d 00:00:00'` على sqlite).
     * ملاحظات صفحة الإجمالي لا تُقفل: لا شهرَ فيها يُحفظ.
     */
    public static function monthIsLocked(FinanceAccount $account, CarbonInterface $month, ?int $unitId = null): bool
    {
        if (self::isTotalPage($account, $unitId)) {
            return false;
        }

        $month = CarbonImmutable::parse($month)->startOfMonth();

        return FinanceMonth::where('finance_account_id', $account->id)
            ->forUnit($unitId)
            ->where('month', '>=', $month->toDateString())
            ->where('month', '<', $month->addMonth()->toDateString())
            ->exists();
    }

    public function isLocked(): bool
    {
        return self::monthIsLocked($this->account, $this->month, $this->finance_unit_id);
    }

    /**
     * الإضافة: شهرٌ بين أول شهرٍ والشهر التالي للدفتر (ما بعده لم يأتِ دوره) — وفي صفحة الإجمالي
     * حتى الشهر الجاري. بإدخال الدفتر والشهر مفتوح، أو بـ`finance.edit` بعد قفله.
     */
    public static function canAdd(?User $user, FinanceAccount $account, CarbonInterface $month, ?FinanceUnit $unit = null): bool
    {
        $month  = CarbonImmutable::parse($month)->startOfMonth();
        $unitId = $unit?->id;
        $last   = self::isTotalPage($account, $unitId)
            ? FinanceCalendar::currentMonth()
            : FinanceCalendar::nextMonth($account, $unitId);

        if (! $user || $month->lessThan(FinanceCalendar::firstMonth()) || $month->greaterThan($last)) {
            return false;
        }

        if ($unit && ! FinanceAccess::canViewUnit($user, $unit)) {
            return false;
        }

        if (self::monthIsLocked($account, $month, $unitId)) {
            return FinanceAccess::canCorrect($user);
        }

        return $unit ? FinanceAccess::canEnterUnit($user, $unit) : FinanceAccess::canEnter($user, $account);
    }

    public function canBeEditedBy(?User $user): bool
    {
        return $this->allows($user, 'finance.edit');
    }

    public function canBeDeletedBy(?User $user): bool
    {
        return $this->allows($user, 'finance.delete');
    }

    /** صاحب صلاحية التصحيح في أي وقت، وكاتبها وحده والشهر مفتوح */
    protected function allows(?User $user, string $ability): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->can($ability)) {
            return true;
        }

        return ! $this->isLocked()
            && $this->created_by === $user->id
            && ($this->unit ? FinanceAccess::canEnterUnit($user, $this->unit) : FinanceAccess::canEnter($user, $this->account));
    }
}
