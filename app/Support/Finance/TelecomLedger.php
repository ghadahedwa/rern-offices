<?php

namespace App\Support\Finance;

use App\Models\FinanceAccount;
use App\Models\FinanceMonth;
use Carbon\CarbonImmutable;

/**
 * سلسلة أرصدة حساب اتصالات — المصدر الواحد للمرحَّل (قرار العميل):
 *
 *     رصيد آخر الشهر = المرحَّل من قبله + الإيراد − المنصرف
 *
 * وأول شهر يأخذ الرصيد الافتتاحي مكان المرحَّل. في الشهر: `income` الإيراد و`amount` المنصرف.
 * ⚠️ **السلسلة دفتر وحدة** (المرحلة ٢، 2026-11-15): لكل وحدة افتتاحيها وشهورها، ولا تختلط وحدتان.
 *
 * ⚠️ **لحظيّ لا مخزَّن**: يُحسب من الصفوف في كل مرة، فتصحيح أكتوبر يصل لكل ما بعده.
 * ⚠️ **الرصيد لا يكون سالباً أبداً** — `firstNegative` يُفحص قبل كل إدخال وتصحيح
 *    (للشهر وللافتتاحي) داخل المعاملة وبعد قفل الحساب. والحذف لآخر شهرٍ وحده فلا يُنتج سالباً.
 */
class TelecomLedger
{
    /**
     * السلسلة كاملة بترتيب الشهور: ['Y-m' => [month, carried, income, expense, balance]].
     *
     * @param  array<int, array{income:int, expense:int}>  $overrides  قيمٌ بديلة لشهورٍ بمعرّفها (فحص تصحيح قبل حفظه)
     * @param  array{month:CarbonImmutable, income:int, expense:int}|null  $insert  شهرٌ جديد يُدرَج بموضعه (فحص إدخالٍ في ثغرة)
     * @param  int[]  $skip  شهورٌ تُستبعد بمعرّفها (فحص حذفٍ قبل تنفيذه)
     */
    public static function chain(FinanceAccount $account, ?int $unitId, array $overrides = [], ?int $opening = null, ?array $insert = null, array $skip = []): array
    {
        $balance = $opening ?? self::opening($account, $unitId);
        $rows    = [];

        $months = FinanceMonth::where('finance_account_id', $account->id)
            ->forUnit($unitId)
            ->whereNotIn('id', $skip)
            ->orderBy('month')
            ->get(['id', 'month', 'income', 'amount'])
            ->map(fn (FinanceMonth $m) => [
                'id'      => $m->id,
                'month'   => CarbonImmutable::parse($m->month)->startOfMonth(),
                'income'  => $overrides[$m->id]['income'] ?? (int) $m->income,
                'expense' => $overrides[$m->id]['expense'] ?? (int) $m->amount,
            ]);

        if ($insert) {
            $months = $months->push(['id' => null] + $insert)->sortBy(fn ($m) => $m['month']->format('Y-m'))->values();
        }

        foreach ($months as $month) {
            $income  = $month['income'];
            $expense = $month['expense'];
            $carried = $balance;
            $balance = $carried + $income - $expense;

            $rows[$month['month']->format('Y-m')] = [
                'month'   => $month['month'],
                'carried' => $carried,
                'income'  => $income,
                'expense' => $expense,
                'balance' => $balance,
            ];
        }

        return $rows;
    }

    /** الرصيد بعد آخر شهرٍ مُدخَل (أو الافتتاحي إن لم يُدخَل شيء) — مرحَّل الشهر التالي */
    public static function closingBalance(FinanceAccount $account, ?int $unitId): int
    {
        $chain = self::chain($account, $unitId);

        return $chain ? end($chain)['balance'] : self::opening($account, $unitId);
    }

    /** الرصيد الافتتاحي للوحدة — صفرٌ إن لم يُكتب (والإدخال مقفولٌ قبله أصلاً) */
    public static function opening(FinanceAccount $account, ?int $unitId): int
    {
        return (int) $account->openingFor($unitId)?->amount;
    }

    /** الرصيد قبل شهرٍ ما (محفوظاً كان أو ثغرةً يُدخَل فيها) — رصيد آخر شهرٍ قبله، أو الافتتاحي */
    public static function balanceBefore(FinanceAccount $account, ?int $unitId, CarbonImmutable $month): int
    {
        $before = collect(self::chain($account, $unitId))->filter(fn ($r) => $r['month']->lessThan($month))->last();

        return $before['balance'] ?? self::opening($account, $unitId);
    }

    /** المرحَّل إلى شهرٍ محفوظ — رصيد ما قبله */
    public static function carriedInto(FinanceAccount $account, ?int $unitId, CarbonImmutable $month): int
    {
        return self::chain($account, $unitId)[$month->format('Y-m')]['carried'] ?? self::closingBalance($account, $unitId);
    }

    /** أول شهرٍ يصير رصيده سالباً لو طُبّقت هذه القيم — null إن بقيت السلسلة سليمة */
    public static function firstNegative(FinanceAccount $account, ?int $unitId, array $overrides = [], ?int $opening = null, ?array $insert = null, array $skip = []): ?array
    {
        foreach (self::chain($account, $unitId, $overrides, $opening, $insert, $skip) as $row) {
            if ($row['balance'] < 0) {
                return $row;
            }
        }

        return null;
    }
}
