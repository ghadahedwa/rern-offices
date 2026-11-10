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
     */
    public static function chain(FinanceAccount $account, array $overrides = [], ?int $opening = null): array
    {
        $balance = $opening ?? (int) $account->opening_balance;
        $rows    = [];

        $months = FinanceMonth::where('finance_account_id', $account->id)
            ->orderBy('month')
            ->get(['id', 'month', 'income', 'amount']);

        foreach ($months as $month) {
            $income  = $overrides[$month->id]['income'] ?? (int) $month->income;
            $expense = $overrides[$month->id]['expense'] ?? (int) $month->amount;
            $carried = $balance;
            $balance = $carried + $income - $expense;

            $rows[$month->month->format('Y-m')] = [
                'month'   => CarbonImmutable::parse($month->month),
                'carried' => $carried,
                'income'  => $income,
                'expense' => $expense,
                'balance' => $balance,
            ];
        }

        return $rows;
    }

    /** الرصيد بعد آخر شهرٍ مُدخَل (أو الافتتاحي إن لم يُدخَل شيء) — مرحَّل الشهر التالي */
    public static function closingBalance(FinanceAccount $account): int
    {
        $chain = self::chain($account);

        return $chain ? end($chain)['balance'] : (int) $account->opening_balance;
    }

    /** المرحَّل إلى شهرٍ محفوظ — رصيد ما قبله */
    public static function carriedInto(FinanceAccount $account, CarbonImmutable $month): int
    {
        return self::chain($account)[$month->format('Y-m')]['carried'] ?? self::closingBalance($account);
    }

    /** أول شهرٍ يصير رصيده سالباً لو طُبّقت هذه القيم — null إن بقيت السلسلة سليمة */
    public static function firstNegative(FinanceAccount $account, array $overrides = [], ?int $opening = null): ?array
    {
        foreach (self::chain($account, $overrides, $opening) as $row) {
            if ($row['balance'] < 0) {
                return $row;
            }
        }

        return null;
    }
}
