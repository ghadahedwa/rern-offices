<?php

namespace App\Livewire\Finance\Concerns;

use App\Models\CourtBuildingItem;
use App\Models\FinanceAccount;
use App\Models\FinanceMonth;
use App\Support\Finance\FinanceCalendar;
use Livewire\Attributes\Url;

/**
 * صفحة حساب مالي — الصفحات الأربع تشترك فيها، وكلٌّ يحدّد حسابه بـ`accountKey()`.
 *
 * جدول شهور **السنة المالية** المختارة (يوليو–يونيو) وإجماليها، وزرّ «إدخال»
 * على **الشهر التالي وحده** (الإدخال بترتيب الشهور) لمن يملك `finance.create`.
 * الحسابان الآخران (أبنية المحاكم · اتصالات) يُفتحان للإدخال في مرحلتيهما.
 */
trait IsAccountPage
{
    /** سنة بداية السنة المالية — تصل من الرابط فتُفحص */
    #[Url(as: 'year')]
    public ?int $year = null;

    abstract protected function accountKey(): string;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('finance.view'), 403);

        // ⚠️ القيمة التالفة أو خارج المدى تُهمَل ولا تُخرج جدولاً فارغاً
        if ($this->year === null || ! FinanceCalendar::isValidFiscalYear($this->year)) {
            $this->year = FinanceCalendar::fiscalYearOf(FinanceCalendar::currentMonth());
        }
    }

    public function updatedYear(): void
    {
        if (! FinanceCalendar::isValidFiscalYear((int) $this->year)) {
            $this->year = FinanceCalendar::fiscalYearOf(FinanceCalendar::currentMonth());
        }
    }

    public function render()
    {
        $account = FinanceAccount::byKey($this->accountKey());

        $data = [
            'account'    => $account,
            'items'      => collect(),
            'rows'       => [],
            'total'      => 0,
            'years'      => FinanceCalendar::fiscalYears(),
            'nextMonth'  => null,
            'canEnter'   => false,
        ];

        if ($account->kind === FinanceAccount::KIND_ITEMIZED) {
            $data['items'] = CourtBuildingItem::active()->ordered()->get();
        }

        if ($account->acceptsEntry()) {
            $months = FinanceCalendar::monthsOf($this->year);

            $records = FinanceMonth::query()
                ->where('finance_account_id', $account->id)
                ->whereBetween('month', [$months[0]->toDateString(), end($months)->toDateString()])
                ->with(['account', 'creator', 'paymentOrders.attachments'])
                ->get()
                ->keyBy(fn (FinanceMonth $m) => $m->month->format('Y-m'));

            $next = FinanceCalendar::nextMonth($account);

            $data['rows'] = array_map(fn ($month) => [
                'month'  => $month,
                'label'  => FinanceCalendar::monthLabel($month),
                'record' => $records->get($month->format('Y-m')),
                'isNext' => $month->equalTo($next),
                'isDue'  => FinanceCalendar::isDue($month),
            ], $months);

            $data['total']     = $records->sum('amount');
            $data['nextMonth'] = $next;
            $data['canEnter']  = auth()->user()->can('finance.create') && FinanceCalendar::isDue($next);
        }

        return view('livewire.finance.account', $data)->title($account->label());
    }
}
