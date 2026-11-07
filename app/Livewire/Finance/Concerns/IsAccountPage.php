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
            'nextDue'    => false,
            'canEnter'   => false,
            'later'      => null,
            'entered'    => 0,
            'monthCount' => 0,
            'yearFrom'   => null,
            'yearTo'     => null,
            'partial'    => false,
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

            $next    = FinanceCalendar::nextMonth($account);
            $nextDue = FinanceCalendar::isDue($next);

            // الجدول: المُدخَل + الشهر التالي إن بدأ — وما بعده سطرٌ واحد مطويّ
            // (سبعة أسطر «لم يبدأ» كانت تملأ الصفحة بلا معلومة — طلب المستخدمة)
            $rows  = [];
            $later = [];
            foreach ($months as $month) {
                $record = $records->get($month->format('Y-m'));

                if ($record || ($nextDue && $month->equalTo($next))) {
                    $rows[] = [
                        'month'  => $month,
                        'label'  => FinanceCalendar::monthLabel($month),
                        'record' => $record,
                    ];
                } else {
                    $later[] = $month;
                }
            }

            $data['rows']       = $rows;
            $data['later']      = $later ? [
                'count' => count($later),
                'from'  => FinanceCalendar::monthLabel($later[0]),
                'to'    => FinanceCalendar::monthLabel(end($later)),
            ] : null;
            $data['total']      = $records->sum('amount');
            $data['entered']    = $records->count();
            $data['monthCount'] = count($months);
            // مدى السنة على الشاشة: الأولى تسعة شهور (أكتوبر–يونيو) فيُسأل «أين يوليو–سبتمبر؟»
            $data['yearFrom']   = FinanceCalendar::monthLabel($months[0]);
            $data['yearTo']     = FinanceCalendar::monthLabel(end($months));
            $data['partial']    = count($months) < 12;
            $data['nextMonth']  = $next;
            $data['nextDue']    = $nextDue;
            $data['canEnter']   = auth()->user()->can('finance.create') && $nextDue;
        }

        return view('livewire.finance.account', $data)->title($account->label());
    }
}
