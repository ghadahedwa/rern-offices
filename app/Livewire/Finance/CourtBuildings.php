<?php

namespace App\Livewire\Finance;

use App\Models\FinanceAccount;
use App\Models\FinanceMonth;
use App\Models\FinanceNote;
use App\Models\FinanceUnit;
use App\Support\Finance\FinanceAccess;
use App\Support\Finance\FinanceCalendar;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * إجمالي أبنية المحاكم — مجموع الوحدات الحسابية (نموذج 2026-11-11 «الشهور × الوحدات»).
 *
 * جدولٌ صفوفه الشهور وأعمدته الوحدات ثم الإجمالي، وصفّ «إجمالي السنة» تحته، و«العناصر» لكل
 * شهرٍ وللسنة. **لا إدخال هنا** — الشهور تُدخَل من صفحات الوحدات، والملاحظات وحدها تُكتب.
 *
 * ⚠️ «مكتمل» = كل الوحدات **المفعَّلة** أدخلت الشهر. والتأخير على الشهر **المنتهي** وحده:
 *    الشهر الجاري يُدخَل بعد انتهائه، فلا يُحسب على وحدةٍ لم تُدخله بعد.
 * ⚠️ العارض يرى أرقام الوحدات كلها (الإجمالي مجموعها أصلاً)، ورابط الوحدة على ما يفتحه وحده.
 */
#[Layout('layouts.app')]
class CourtBuildings extends Component
{
    use Concerns\IsAccountPage;

    protected function accountKey(): string
    {
        return FinanceAccount::COURT_BUILDINGS;
    }

    public function render()
    {
        $account = FinanceAccount::byKey($this->accountKey());
        $months  = FinanceCalendar::monthsOf($this->year);
        $current = FinanceCalendar::currentMonth();

        $records = FinanceMonth::where('finance_account_id', $account->id)
            ->whereNotNull('finance_unit_id')
            ->whereBetween('month', [$months[0]->toDateString(), end($months)->toDateString()])
            ->with('items.item')
            ->get();

        // الأعمدة: المفعَّلة، والمعطَّلة التي لها شهرٌ في السنة (أرقامها جزءٌ من الإجمالي)
        $units = FinanceUnit::ordered()->get()
            ->filter(fn (FinanceUnit $u) => $u->is_active || $records->contains('finance_unit_id', $u->id))
            ->values();
        $required = $units->where('is_active', true)->pluck('id');

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
            foreach ($entries as $entry) {
                foreach ($entry->items as $item) {
                    $items[$item->item->name] = ($items[$item->item->name] ?? 0) + $item->amount;
                    $yearItems[$item->item->name] = ($yearItems[$item->item->name] ?? 0) + $item->amount;
                }
            }

            $rows[] = [
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
        }

        $incomplete = collect($rows)->reject(fn ($r) => $r['complete']);
        $oldestLate = $incomplete->first(fn ($r) => $r['ended']);

        $notes = FinanceNote::where('finance_account_id', $account->id)
            ->whereNull('finance_unit_id')
            ->whereBetween('month', [$months[0]->toDateString(), end($months)->toDateString().' 23:59:59'])
            ->with('author')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (FinanceNote $n) => $n->month->format('Y-m'));

        $noteAdd = [];
        foreach ($rows as $row) {
            $noteAdd[$row['key']] = FinanceNote::canAdd(auth()->user(), $account, $row['month']);
        }

        return view('livewire.finance.court-total', [
            'account'     => $account,
            'years'       => FinanceCalendar::fiscalYears(),
            'units'       => $units,
            'linked'      => FinanceAccess::viewableUnits(auth()->user())->pluck('id'),
            'unitNames'   => $units->pluck('name', 'id'),
            'rows'        => $rows,
            'later'       => $later ? [
                'count' => count($later),
                'from'  => FinanceCalendar::monthLabel($later[0]),
                'to'    => FinanceCalendar::monthLabel(end($later)),
            ] : null,
            'unitTotals'  => $records->groupBy('finance_unit_id')->map->sum('amount'),
            'yearTotal'   => $records->sum('amount'),
            'yearItems'   => $yearItems,
            'incomplete'  => $incomplete,
            'oldestLate'  => $oldestLate,
            'required'    => $required->count(),
            'yearFrom'    => FinanceCalendar::monthLabel($months[0]),
            'yearTo'      => FinanceCalendar::monthLabel(end($months)),
            'notes'       => $notes,
            'noteAdd'     => $noteAdd,
        ])->title($account->label());
    }
}
