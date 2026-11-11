<?php

namespace App\Livewire\Finance\Concerns;

use App\Models\FinanceAccount;
use App\Models\FinanceMonth;
use App\Models\FinanceNote;
use App\Support\Finance\FinanceAccess;
use App\Support\Finance\FinanceCalendar;
use App\Support\Finance\FinanceHistory;
use Carbon\CarbonImmutable;
use Flux\Flux;
use App\Support\Finance\TelecomLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;

/**
 * صفحة حساب مالي — الصفحات الأربع تشترك فيها، وكلٌّ يحدّد حسابه بـ`accountKey()`.
 *
 * جدول شهور **السنة المالية** المختارة (يوليو–يونيو) وإجماليها، وزرّ «إدخال»
 * على **الشهر التالي وحده** (الإدخال بترتيب الشهور) لمن يملك إدخال الحساب (`FinanceAccess::canEnter`).
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

    // ── اتصالات: الرصيد الافتتاحي ──
    public string $openingInput = '';
    public string $openingReason = '';
    public bool $correctingOpening = false;

    // ── الملاحظات (المرحلة و) ──
    /** مسوّدة ملاحظةٍ جديدة لكل شهر: ['Y-m' => '…'] */
    public array $noteDrafts = [];

    #[Locked]
    public ?int $editingNoteId = null;

    public string $editingNoteBody = '';

    /** ملاحظةٌ مرشّحة للحذف — المودال المشترك نفسه بلا خانة سبب */
    #[Locked]
    public ?int $deletingNoteId = null;

    abstract protected function accountKey(): string;

    /** الشهر من مفتاح الرابط `Y-m` — التالف null */
    protected static function monthFromKey(string $key): ?CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $key)) {
            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m', $key) ?: null;
    }

    protected function noteRules(string $field): array
    {
        return [$field => ['required', 'string', 'max:'.FinanceNote::MAX_LENGTH]];
    }

    public function addNote(string $key): void
    {
        $account = FinanceAccount::byKey($this->accountKey());
        $month   = self::monthFromKey($key);

        abort_unless($month && FinanceNote::canAdd(auth()->user(), $account, $month), 403);

        $body = trim((string) ($this->noteDrafts[$key] ?? ''));
        $this->noteDrafts[$key] = $body;
        $this->validate(
            $this->noteRules("noteDrafts.$key"),
            ["noteDrafts.$key.required" => __('home.fin_note_required')],
        );

        FinanceNote::create([
            'finance_account_id' => $account->id,
            'month'              => $month->toDateString(),
            'body'               => $body,
            'created_by'         => auth()->id(),
        ]);

        unset($this->noteDrafts[$key]);
    }

    protected function noteOf(int $id): FinanceNote
    {
        // ⚠️ معرّفٌ من المتصفح: ملاحظة حسابٍ آخر لا تُفتح من صفحة هذا الحساب
        $note = FinanceNote::with('account')
            ->whereKey($id)
            ->whereHas('account', fn ($q) => $q->where('key', $this->accountKey()))
            ->first();
        abort_if($note === null, 404);

        return $note;
    }

    public function startEditNote(int $id): void
    {
        $note = $this->noteOf($id);
        abort_unless($note->canBeEditedBy(auth()->user()), 403);

        $this->editingNoteId   = $note->id;
        $this->editingNoteBody = $note->body;
        $this->resetErrorBag();
    }

    public function cancelEditNote(): void
    {
        $this->editingNoteId   = null;
        $this->editingNoteBody = '';
    }

    /** ⚠️ الصلاحية تُفحص من جديد: الشهر قد يُقفل والتعديل مفتوح، فيصير لصاحب `finance.edit` وحده */
    public function saveNote(): void
    {
        if ($this->editingNoteId === null) {
            return;
        }

        $note = $this->noteOf($this->editingNoteId);
        abort_unless($note->canBeEditedBy(auth()->user()), 403);

        $this->editingNoteBody = trim($this->editingNoteBody);
        $this->validate($this->noteRules('editingNoteBody'), ['editingNoteBody.required' => __('home.fin_note_required')]);

        if ($note->body !== $this->editingNoteBody) {
            DB::transaction(function () use ($note) {
                if ($note->isLocked()) {
                    FinanceHistory::recordNote(FinanceHistory::EVENT_NOTE_EDITED, $note->account, $note->month, $note->body, $this->editingNoteBody);
                }
                $note->update(['body' => $this->editingNoteBody]);
            });
        }

        $this->cancelEditNote();
    }

    public function confirmDeleteNote(int $id): void
    {
        $note = $this->noteOf($id);
        abort_unless($note->canBeDeletedBy(auth()->user()), 403);

        $this->deletingId      = null;
        $this->deletingNoteId  = $note->id;
        $this->deletingLabel   = \Illuminate\Support\Str::limit($note->body, 80);
        $this->deletingWarning = '';
        $this->showDelete      = true;
    }

    protected function deleteNote(): void
    {
        $note = $this->noteOf($this->deletingNoteId);
        abort_unless($note->canBeDeletedBy(auth()->user()), 403);

        DB::transaction(function () use ($note) {
            if ($note->isLocked()) {
                FinanceHistory::recordNote(FinanceHistory::EVENT_NOTE_DELETED, $note->account, $note->month, $note->body, null);
            }
            $note->delete();
        });

        $this->showDelete     = false;
        $this->deletingNoteId = null;
    }

    protected static function openingDigits(string $value): string
    {
        return preg_replace('/[\s,٬،]/u', '', \App\Support\ArabicDigits::toLatin(trim($value))) ?? '';
    }

    /**
     * يُكتب **مرة واحدة** بإدخال الحسابات العامة ويُقفل كالشهر (قرار المستخدمة). ⚠️ الحارسان في
     * الإجراء: الصلاحية، و«لم يُكتب بعد» تحت قفل الحساب — نداءٌ ثانٍ لا يستبدل رصيداً قائماً.
     */
    public function saveOpening(): void
    {
        abort_unless(FinanceAccess::canEnter(auth()->user(), FinanceAccount::byKey($this->accountKey())), 403);

        $this->openingInput = self::openingDigits($this->openingInput);
        $this->validate(
            ['openingInput' => ['required', 'regex:/^\d{1,12}$/']],
            ['openingInput.required' => __('home.fin_amount_invalid'), 'openingInput.regex' => __('home.fin_amount_invalid')],
        );

        $saved = DB::transaction(function () {
            $account = FinanceAccount::where('key', $this->accountKey())->lockForUpdate()->firstOrFail();
            abort_unless($account->isLedger(), 404);

            if ($account->opening_balance !== null) {
                return false;
            }

            $account->update([
                'opening_balance' => (int) $this->openingInput,
                'opening_set_by'  => auth()->id(),
                'opening_set_at'  => now(),
            ]);

            return true;
        });

        $this->openingInput = '';
        Flux::toast(
            variant: $saved ? 'success' : 'danger',
            text: __($saved ? 'home.fin_opening_saved' : 'home.fin_opening_already_set'),
        );
    }

    public function startOpeningCorrection(): void
    {
        abort_unless(auth()->user()?->can('finance.edit'), 403);

        $this->openingInput      = (string) FinanceAccount::byKey($this->accountKey())->opening_balance;
        $this->openingReason     = '';
        $this->correctingOpening = true;
        $this->resetErrorBag();
    }

    /**
     * تصحيح الرصيد الافتتاحي — بسببٍ وسجلّ كالشهر. ⚠️ يغيّر مرحَّل الشهور كلها، فيُرفض إن صار
     * أي شهرٍ سالباً، والرسالة تسمّيه.
     */
    public function correctOpening(): void
    {
        abort_unless(auth()->user()?->can('finance.edit'), 403);

        $this->openingInput  = self::openingDigits($this->openingInput);
        $this->openingReason = trim($this->openingReason);
        $this->validate(
            ['openingInput' => ['required', 'regex:/^\d{1,12}$/'], 'openingReason' => ['required', 'string', 'min:5', 'max:1000']],
            [
                'openingInput.required'  => __('home.fin_amount_invalid'),
                'openingInput.regex'     => __('home.fin_amount_invalid'),
                'openingReason.required' => __('home.fin_reason_required'),
                'openingReason.min'      => __('home.fin_reason_required'),
            ],
        );

        DB::transaction(function () {
            $account = FinanceAccount::where('key', $this->accountKey())->lockForUpdate()->firstOrFail();
            abort_unless($account->isLedger() && $account->opening_balance !== null, 404);

            $old = (int) $account->opening_balance;
            $new = (int) $this->openingInput;

            if ($old === $new) {
                throw ValidationException::withMessages(['openingReason' => __('home.fin_correction_unchanged')]);
            }

            $negative = TelecomLedger::firstNegative($account, [], $new);
            if ($negative) {
                throw ValidationException::withMessages(['openingInput' => __('home.fin_correction_negative', [
                    'month'   => FinanceCalendar::monthLabel($negative['month']),
                    'balance' => number_format($negative['balance']),
                ])]);
            }

            FinanceHistory::record(
                FinanceHistory::EVENT_CORRECTED, $account, FinanceHistory::OPENING, $this->openingReason,
                ['amount' => $old, 'orders' => []], ['amount' => $new, 'orders' => []],
            );
            $account->update(['opening_balance' => $new]);
        });

        $this->correctingOpening = false;
        $this->openingInput      = '';
        $this->openingReason     = '';
        Flux::toast(variant: 'success', text: __('home.fin_opening_corrected'));
    }

    public function mount(): void
    {
        abort_unless(FinanceAccess::canView(auth()->user(), FinanceAccount::byKey($this->accountKey())), 403);

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

        $this->deletingNoteId  = null;
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
        if ($this->showDelete && $this->deletingNoteId !== null) {
            $this->deleteNote();

            return;
        }

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
            'notes'      => collect(),
            'noteAdd'    => [],
        ];

        if ($account->acceptsEntry()) {
            $months = FinanceCalendar::monthsOf($this->year);

            $records = FinanceMonth::query()
                ->where('finance_account_id', $account->id)
                ->whereBetween('month', [$months[0]->toDateString(), end($months)->toDateString()])
                ->with(['account', 'creator', 'paymentOrders.attachments', 'items.item'])
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

            // الملاحظات بشهرها، وهل يُضيف المستخدم على كل صفّ معروض (الحارس نفسه في addNote)
            $data['notes'] = FinanceNote::where('finance_account_id', $account->id)
                ->whereBetween('month', [$months[0]->toDateString(), end($months)->toDateString().' 23:59:59'])
                ->with('author')
                ->orderBy('id')
                ->get()
                ->groupBy(fn (FinanceNote $n) => $n->month->format('Y-m'));
            foreach ($rows as $row) {
                $data['noteAdd'][$row['month']->format('Y-m')] = FinanceNote::canAdd(auth()->user(), $account, $row['month']);
            }
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

            if ($account->isLedger()) {
                // السلسلة كلها لا شهور السنة وحدها — مرحَّل يوليو من يونيو السنة السابقة
                $chain = TelecomLedger::chain($account);
                $data['ledger']        = $chain;
                $data['yearIncome']    = $records->sum('income');
                $data['yearExpense']   = $records->sum('amount');
                $data['balanceNow']    = $chain ? end($chain)['balance'] : $account->opening_balance;
                $data['balanceAfter']  = $chain ? FinanceCalendar::monthLabel(end($chain)['month']) : null;
                $data['openingBy']     = $account->openingSetter?->name;
                // صفّ الافتتاحي في جدول سنته الأولى وحدها
                $data['showOpening']   = $account->opening_balance !== null && $months[0]->lessThanOrEqualTo(FinanceCalendar::firstMonth());
                $data['canSetOpening'] = $account->needsOpening() && FinanceAccess::canEnter(auth()->user(), $account);
            }
            $data['nextMonth']  = $next;
            $data['nextDue']    = $nextDue;
            $data['canEnter']   = FinanceAccess::canEnter(auth()->user(), $account) && $nextDue && ! $account->needsOpening();
            $data['history']    = FinanceHistory::forAccount($account);
            $data['lastId']     = $this->lastMonth($account)?->id;
            $data['canCorrect'] = auth()->user()->can('finance.edit');
            $data['canDelete']  = auth()->user()->can('finance.delete');
        }

        return view('livewire.finance.account', $data)->title($account->label());
    }
}
