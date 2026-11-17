<?php

namespace App\Support\Finance;

use App\Models\FinanceAccount;
use App\Models\FinanceMonth;
use App\Models\FinanceUnit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * جدول «الوحدات × الشهور» لحسابٍ في سنةٍ مالية — المصدر الواحد لجداول «الإجمالي العام» الأربعة
 * (المرحلة ٣ · 2026-11-17). كان منطق صفحة إجمالي كل حساب (`BuildsUnitsTotal`).
 *
 * ⚠️ «مكتمل» = كل الوحدات **المفعَّلة** أدخلت الشهر. والتأخير على الشهر **المنتهي** وحده:
 *    الشهر الجاري يُدخَل بعد انتهائه، فلا يُحسب على وحدةٍ لم تُدخله بعد.
 * ⚠️ الصفوف المفعَّلة والمعطَّلة التي لها شهرٌ في السنة (أرقامها جزءٌ من الإجمالي).
 *
 * اتصالات (الشكل «ب» الذي اختارته المستخدمة): الخلية رصيد آخر الشهر وتحته الإيراد والمنصرف،
 * وعمود «أول السنة» قبل الشهور (الافتتاحي في السنة الأولى)، وعمود السنة رصيدها الأخير وحركتها.
 * الأرصدة من `TelecomLedger` لا من هنا.
 */
class UnitsTotal
{
    public static function build(FinanceAccount $account, int $year, Collection $units): array
    {
        $months  = FinanceCalendar::monthsOf($year);
        $current = FinanceCalendar::currentMonth();
        $from    = $months[0];
        $to      = end($months);

        $records = FinanceMonth::where('finance_account_id', $account->id)
            ->whereNotNull('finance_unit_id')
            ->whereBetween('month', [$from->toDateString(), $to->toDateString()])
            ->when($account->isItemized(), fn ($q) => $q->with('items.item'))
            ->get();

        $units = $units
            ->filter(fn (FinanceUnit $u) => $u->is_active || $records->contains('finance_unit_id', $u->id))
            ->values();
        $required = $units->where('is_active', true)->pluck('id');

        $ledger = $account->isLedger() ? self::ledger($account, $units, $from, $to) : null;
        $byMonth = $records->groupBy(fn (FinanceMonth $m) => $m->month->format('Y-m'));

        $rows      = [];
        $later     = [];
        $yearItems = [];
        foreach ($months as $month) {
            if ($month->greaterThan($current)) {
                $later[] = $month;

                continue;
            }

            $key     = $month->format('Y-m');
            $entries = ($byMonth->get($key) ?? collect())->keyBy('finance_unit_id');
            $missing = $required->reject(fn ($id) => $entries->has($id));

            $items = [];
            if ($account->isItemized()) {
                foreach ($entries as $entry) {
                    foreach ($entry->items as $item) {
                        $items[$item->item->name] = ($items[$item->item->name] ?? 0) + $item->amount;
                        $yearItems[$item->item->name] = ($yearItems[$item->item->name] ?? 0) + $item->amount;
                    }
                }
            }

            $row = [
                'month'    => $month,
                'key'      => $key,
                'label'    => FinanceCalendar::monthLabel($month),
                'cells'    => $entries->map->amount,
                'total'    => $entries->sum('amount'),
                'entered'  => $required->count() - $missing->count(),
                'missing'  => $missing->values(),
                'complete' => $missing->isEmpty(),
                'ended'    => $month->lessThan($current),
                'items'    => $items,
            ];

            if ($ledger) {
                // الخلية: رصيد آخر الشهر + إيراده ومنصرفه — للوحدات التي أدخلته وحدها
                $row['ledger'] = $entries->keys()->mapWithKeys(fn ($id) => [$id => $ledger['chains'][$id][$key]])->all();
                $row['ledgerTotal'] = [
                    'balance' => collect($row['ledger'])->sum('balance'),
                    'income'  => collect($row['ledger'])->sum('income'),
                    'expense' => collect($row['ledger'])->sum('expense'),
                ];
            }

            $rows[] = $row;
        }

        $incomplete = collect($rows)->reject(fn ($r) => $r['complete']);
        $lastEnded  = collect($rows)->filter(fn ($r) => $r['ended'])->last();

        return [
            'account'    => $account,
            'units'      => $units,
            'rows'       => $rows,
            'later'      => $later ? [
                'count' => count($later),
                'from'  => FinanceCalendar::monthLabel($later[0]),
                'to'    => FinanceCalendar::monthLabel(end($later)),
            ] : null,
            'unitTotals' => $records->groupBy('finance_unit_id')->map->sum('amount'),
            'yearTotal'  => $records->sum('amount'),
            'yearItems'  => $yearItems,
            'incomplete' => $incomplete,
            'oldestLate' => $incomplete->first(fn ($r) => $r['ended']),
            'lastEnded'  => $lastEnded,
            'required'   => $required->count(),
            'ledger'     => $ledger,
        ];
    }

    /**
     * اتصالات: سلسلة كل وحدة ورصيدها أول السنة وآخرها. ⚠️ رصيد أول السنة **المرحَّل إليها** (آخر شهرٍ
     * قبلها، والافتتاحي إن لم يسبقها شهر) — ووحدةٌ بلا افتتاحي رصيدها «—» لا صفر.
     */
    protected static function ledger(FinanceAccount $account, Collection $units, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $chains = [];
        $start  = [];
        $year   = [];

        foreach ($units as $unit) {
            $chain   = TelecomLedger::chain($account, $unit->id);
            $opening = $account->openingFor($unit->id)?->amount;

            $before = collect($chain)->filter(fn ($r) => $r['month']->lessThan($from))->last();
            $inYear = collect($chain)->filter(fn ($r) => $r['month']->betweenIncluded($from, $to));

            $chains[$unit->id] = $chain;
            $start[$unit->id]  = $before['balance'] ?? $opening;
            $year[$unit->id]   = [
                'balance' => $inYear->isNotEmpty() ? $inYear->last()['balance'] : $start[$unit->id],
                'income'  => $inYear->sum('income'),
                'expense' => $inYear->sum('expense'),
                'entered' => $inYear->isNotEmpty(),
            ];
        }

        $known = collect($year)->filter(fn ($y) => $y['balance'] !== null);

        return [
            'chains'     => $chains,
            'start'      => $start,
            'startTotal' => collect($start)->sum(),
            'firstYear'  => $from->lessThanOrEqualTo(FinanceCalendar::firstMonth()),
            'year'       => $year,
            'yearTotal'  => [
                'balance' => $known->sum('balance'),
                'income'  => $known->sum('income'),
                'expense' => $known->sum('expense'),
            ],
        ];
    }
}
