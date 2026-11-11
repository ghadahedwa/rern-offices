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

    protected $fillable = ['finance_account_id', 'month', 'body', 'created_by'];

    protected $casts = [
        'month' => 'date',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'finance_account_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** هل حُفظت أرقام الشهر؟ — ⚠️ نطاقٌ لا مساواة (`'Y-m-d 00:00:00'` على sqlite) */
    public static function monthIsLocked(FinanceAccount $account, CarbonInterface $month): bool
    {
        $month = CarbonImmutable::parse($month)->startOfMonth();

        return FinanceMonth::where('finance_account_id', $account->id)
            ->where('month', '>=', $month->toDateString())
            ->where('month', '<', $month->addMonth()->toDateString())
            ->exists();
    }

    public function isLocked(): bool
    {
        return self::monthIsLocked($this->account, $this->month);
    }

    /**
     * الإضافة: شهرٌ بين أول شهرٍ والشهر التالي للإدخال (ما بعده لم يأتِ دوره)، بإدخال الحساب
     * والشهر مفتوح، أو بـ`finance.edit` بعد قفله.
     */
    public static function canAdd(?User $user, FinanceAccount $account, CarbonInterface $month): bool
    {
        $month = CarbonImmutable::parse($month)->startOfMonth();

        if (! $user || $month->lessThan(FinanceCalendar::firstMonth())
            || $month->greaterThan(FinanceCalendar::nextMonth($account))) {
            return false;
        }

        return self::monthIsLocked($account, $month)
            ? FinanceAccess::canCorrect($user)
            : FinanceAccess::canEnter($user, $account);
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
            && FinanceAccess::canEnter($user, $this->account);
    }
}
