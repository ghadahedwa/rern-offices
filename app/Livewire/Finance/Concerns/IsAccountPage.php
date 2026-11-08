<?php

namespace App\Livewire\Finance\Concerns;

use App\Models\CourtBuildingItem;
use App\Models\FinanceAccount;
use App\Models\FinanceMonth;
use App\Support\Finance\FinanceCalendar;
use App\Support\Finance\FinanceHistory;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
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

    // ── حذف الشهر (المودال المشترك) ──
    public bool $showDelete = false;
    public string $deletingLabel = '';
    public string $deletingWarning = '';

    #[Locked]
    public ?int $deletingId = null;

    /** سبب الحذف — إجباري كالتصحيح، ويبقى في السجلّ بعد زوال الشهر */
    public string $deleteReason = '';

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

    /**
     * آخر شهرٍ مُدخَل في الحساب — **وحده يُحذف** (قرار المستخدمة): الإدخال بالترتيب، وحذف
     * شهرٍ في الوسط يترك ثغرةً لا يملؤها الإدخال التالي. والخطأ فيما قبله يُصحَّح بالتعديل.
     */
    protected function lastMonth(FinanceAccount $account): ?FinanceMonth
    {
        return FinanceMonth::where('finance_account_id', $account->id)->orderByDesc('month')->first();
    }

    public function confirmDelete(int $id): void
    {
        abort_unless(auth()->user()?->can('finance.delete'), 403);

        $account = FinanceAccount::byKey($this->accountKey());
        $last    = $this->lastMonth($account);
        abort_unless($last && $last->id === $id, 403);

        $this->deletingId      = $id;
        $this->deletingLabel   = FinanceCalendar::monthLabel(CarbonImmutable::parse($last->month));
        $this->deletingWarning = __('home.fin_delete_warning');
        $this->deleteReason    = '';
        $this->resetErrorBag();
        $this->showDelete = true;
    }

    /**
     * ⚠️ الحارسان في الإجراء لا في القالب: الصلاحية، و«آخر شهر» يُعاد فحصه تحت قفل الحساب —
     *    شهرٌ أُدخل بعد فتح المودال يجعل المحدَّد في الوسط.
     * ⚠️ الصور تُحذف بالموديل (`FinanceMonth::booted`) فتزول ملفاتها بعد الاعتماد.
     */
    public function deleteRow(): void
    {
        if (! $this->showDelete || $this->deletingId === null) {
            return;
        }

        abort_unless(auth()->user()?->can('finance.delete'), 403);

        $this->deleteReason = trim($this->deleteReason);
        $this->validate(
            ['deleteReason' => ['required', 'string', 'min:5', 'max:1000']],
            ['deleteReason.required' => __('home.fin_reason_required'), 'deleteReason.min' => __('home.fin_reason_required')],
        );

        $account = FinanceAccount::byKey($this->accountKey());

        $deleted = DB::transaction(function () use ($account) {
            FinanceAccount::whereKey($account->id)->lockForUpdate()->first();

            $last = $this->lastMonth($account);
            if (! $last || $last->id !== $this->deletingId) {
                return null;
            }

            $month = CarbonImmutable::parse($last->month);
            FinanceHistory::record(
                FinanceHistory::EVENT_DELETED, $account, $month, $this->deleteReason,
                FinanceHistory::snapshot($last), null,
                imagesRemoved: $last->paymentOrders->sum(fn ($o) => $o->attachments->count()),
            );
            $last->delete();

            return $month;
        });

        $this->showDelete = false;
        $this->deletingId = null;

        if ($deleted === null) {
            Flux::toast(variant: 'danger', text: __('home.fin_delete_not_last'));

            return;
        }

        Flux::toast(variant: 'success', text: __('home.fin_month_deleted', ['month' => FinanceCalendar::monthLabel($deleted)]));
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
            'history'    => collect(),
            'lastId'     => null,
            'canCorrect' => false,
            'canDelete'  => false,
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
            $data['history']    = FinanceHistory::forAccount($account);
            $data['lastId']     = $this->lastMonth($account)?->id;
            $data['canCorrect'] = auth()->user()->can('finance.edit');
            $data['canDelete']  = auth()->user()->can('finance.delete');
        }

        return view('livewire.finance.account', $data)->title($account->label());
    }
}
