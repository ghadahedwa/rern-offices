<?php

namespace App\Livewire\Finance;

use App\Models\CourtBuildingItem;
use App\Models\FinanceAccount;
use App\Models\FinanceAttachment;
use App\Models\FinanceMonth;
use App\Models\FinanceMonthItem;
use App\Models\FinancePaymentOrder;
use App\Support\ArabicDigits;
use App\Support\Finance\AmountInWords;
use App\Support\Finance\FinanceCalendar;
use App\Support\Finance\FinanceHistory;
use App\Support\Finance\TelecomLedger;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * إدخال أرقام شهر — المرحلة (ب): الحسابان ذوا المبلغ الواحد (الميكنة · وزارة المالية).
 *
 * الخطوتان: الفورم ← **شاشة تأكيد** بالأرقام والتفقيط وتنبيه القفل ← الحفظ.
 *
 * ⚠️ **الشهر يحسبه النظام لا يختاره المستخدم** (الإدخال بترتيب الشهور)، ويُعاد حسابه
 *    عند الحفظ تحت قفل: شهرٌ أدخله مستخدمٌ آخر في الأثناء يُرفض الحفظ عليه.
 * ⚠️ **الشهر يُقفل لحظة حفظه** — وتصحيحه في المكوّن نفسه (`finance.correct`) بـ`finance.edit`
 *    و**سببٍ إجباري**، ويُسجَّل قبله وبعده في `FinanceHistory`. الشهر نفسه لا يتغيّر بالتصحيح.
 * ⚠️ **أمر دفع واحد على الأقل، ولكلٍّ صورة واحدة على الأقل** (قرار العميل) — والصور
 *    تُخزَّن داخل المعاملة وتُحذف مساراتها إن تراجعت.
 */
#[Layout('layouts.app')]
class MonthEntry extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $accountKey = '';

    /** الشهر المطلوب إدخاله (`Y-m-d`) — null إن لم يبدأ بعد */
    #[Locked]
    public ?string $month = null;

    public string $amount = '';

    /** أوامر الدفع: [['number' => '', 'issued_on' => '']] */
    public array $orders = [];

    /** صور كل أمر بفهرسه — تُجمَّع عبر `picked` (الاختيار يُضاف ولا يستبدل) */
    public array $orderFiles = [];

    /** ما وصل من آخر اختيار لكل أمر — ينتقل فوراً إلى `orderFiles` */
    public array $picked = [];

    public bool $reviewing = false;

    /**
     * شهرٌ بلا أمر دفع — **لشهرٍ مبلغه صفر وحده** (قرار المستخدمة 2026-11-07).
     * بلا هذا يقف الشهر الذي لا حركة فيه أمام كل ما بعده (الإدخال بالترتيب).
     */
    public bool $noOrder = false;

    /** الشهر المُصحَّح — null في الإدخال */
    #[Locked]
    public ?int $editingId = null;

    /** صور الأمر المحفوظة الباقية بفهرسه: [i => [معرّف…]] — التصحيح وحده */
    public array $kept = [];

    /** سبب التصحيح — إجباري (قرار العميل) */
    public string $reason = '';

    /**
     * أبنية المحاكم: عناصر الفورم بترتيبها — المفعَّلة عند الإدخال، وعناصر الشهر نفسه عند
     * التصحيح (عنصرٌ عُطِّل بعده يبقى، وعنصرٌ أُضيف بعده لا يُطلب في شهرٍ مقفول).
     */
    #[Locked]
    public array $itemIds = [];

    /** قيمة كل عنصر بمعرّفه: [id => '…'] — الإجمالي يُحسب منها لا يُكتب */
    public array $items = [];

    /** اتصالات: الحساب ذو الرصيد — إيراد ومنصرف، و`amount` يصير المنصرف بعد التحقق */
    #[Locked]
    public bool $ledger = false;

    public string $income = '';

    public string $expense = '';

    /** مسارات خُزِّنت في هذا الحفظ — تُحذف إن تراجعت المعاملة */
    protected array $storedPaths = [];

    public function mount(string $account, ?string $period = null): void
    {
        abort_unless(auth()->user()?->can($period === null ? 'finance.create' : 'finance.edit'), 403);

        $model = FinanceAccount::bySlug($account);
        abort_unless($model->acceptsEntry(), 404);

        $this->accountKey = $model->key;
        $this->ledger     = $model->isLedger();

        if ($period !== null) {
            $this->loadForCorrection($model, $period);

            return;
        }

        $next = FinanceCalendar::nextMonth($model);
        // اتصالات بلا رصيدٍ افتتاحي: لا شهر يُفتح — الصفحة تردّه إلى صفحة الحساب ليكتبه
        $this->month = FinanceCalendar::isDue($next) && ! $model->needsOpening() ? $next->toDateString() : null;

        $this->orders     = [['number' => '', 'issued_on' => '']];
        $this->orderFiles = [[]];

        if ($model->isItemized()) {
            $this->itemIds = CourtBuildingItem::active()->ordered()->pluck('id')->all();
            $this->items   = array_fill_keys($this->itemIds, '');
        }
    }

    /** يملأ الفورم بالشهر المحفوظ — الصور المحفوظة في `kept` والجديدة في `orderFiles` */
    protected function loadForCorrection(FinanceAccount $account, string $period): void
    {
        $month  = CarbonImmutable::createFromFormat('!Y-m', $period) ?: null;
        $record = $month ? $this->findRecord($account, $month) : null;
        abort_if($record === null, 404);

        $record->load('paymentOrders.attachments', 'items');

        if ($account->isItemized()) {
            $this->itemIds = $record->items->pluck('court_building_item_id')->all();
            $this->items   = $record->items->mapWithKeys(fn ($i) => [$i->court_building_item_id => (string) $i->amount])->all();
        }

        $this->editingId = $record->id;
        $this->month     = $month->toDateString();
        $this->amount    = (string) $record->amount;
        $this->noOrder   = $record->paymentOrders->isEmpty();

        if ($this->ledger) {
            $this->income  = (string) (int) $record->income;
            $this->expense = (string) $record->amount;
        }

        $this->orders = $record->paymentOrders->map(fn (FinancePaymentOrder $o) => [
            'id'        => $o->id,
            'number'    => $o->number,
            'issued_on' => $o->issued_on?->toDateString() ?? '',
        ])->values()->all() ?: [['id' => null, 'number' => '', 'issued_on' => '']];

        $this->kept = $record->paymentOrders
            ->map(fn (FinancePaymentOrder $o) => $o->attachments->pluck('id')->all())
            ->values()->all() ?: [[]];
        $this->orderFiles = array_fill(0, count($this->orders), []);
    }

    /** ⚠️ نطاقٌ لا مساواة: العمود المصبوب `'date'` يُكتب `'Y-m-d 00:00:00'` على sqlite */
    protected function findRecord(FinanceAccount $account, CarbonImmutable $month): ?FinanceMonth
    {
        return FinanceMonth::where('finance_account_id', $account->id)
            ->where('month', '>=', $month->toDateString())
            ->where('month', '<', $month->addMonth()->toDateString())
            ->first();
    }

    protected function account(): FinanceAccount
    {
        return FinanceAccount::byKey($this->accountKey);
    }

    // ── أوامر الدفع ──────────────────────────────────────

    public function addOrder(): void
    {
        if (count($this->orders) >= FinancePaymentOrder::MAX_PER_MONTH) {
            return;
        }

        $this->orders[]     = ['id' => null, 'number' => '', 'issued_on' => ''];
        $this->orderFiles[] = [];
        $this->kept[]       = [];
    }

    public function removeOrder(int $index): void
    {
        if (count($this->orders) <= 1 || ! isset($this->orders[$index])) {
            return;
        }

        unset($this->orders[$index], $this->orderFiles[$index], $this->picked[$index], $this->kept[$index]);
        $this->orders     = array_values($this->orders);
        $this->orderFiles = array_values($this->orderFiles);
        $this->kept       = array_values($this->kept);
        $this->picked     = [];
        $this->resetErrorBag();
    }

    /**
     * ⚠️ يقرأ كل `picked` لا المفتاح وحده: Livewire يُبلغ مسار الخاصية بصيغٍ مختلفة
     *    للرفع المتعدد، والنتيجة واحدة — كل ما وصل ينتقل إلى أمره ثم يُفرَّغ.
     */
    public function updatedPicked(): void
    {
        foreach ($this->picked as $index => $files) {
            if (! isset($this->orders[$index])) {
                continue;
            }

            $this->resetErrorBag("orderFiles.$index");
            $rejected = [];
            $overflow = false;

            foreach ((array) $files as $file) {
                if (Validator::make(['f' => $file], ['f' => FinanceAttachment::FILE_RULES])->fails()) {
                    $rejected[] = $file->getClientOriginalName();
                    continue;
                }
                if ($this->imageCount($index) >= FinanceAttachment::MAX_PER_ORDER) {
                    $overflow = true;
                    continue;
                }
                $this->orderFiles[$index][] = $file;
            }

            $messages = [];
            if ($rejected) {
                $messages[] = __('home.fin_image_rejected', ['names' => implode('، ', $rejected)]);
            }
            if ($overflow) {
                $messages[] = __('home.fin_images_max', ['max' => FinanceAttachment::MAX_PER_ORDER]);
            }
            if ($messages) {
                $this->addError("orderFiles.$index", implode(' — ', $messages));
            }
        }

        $this->picked = [];
    }

    /** صور الأمر: المحفوظة الباقية + الجديدة */
    protected function imageCount(int $index): int
    {
        return count($this->orderFiles[$index] ?? []) + count($this->kept[$index] ?? []);
    }

    /** إزالة صورةٍ محفوظة من الأمر — تُحذف فعلاً عند حفظ التصحيح لا الآن */
    public function removeKept(int $order, int $id): void
    {
        $this->kept[$order] = array_values(array_diff($this->kept[$order] ?? [], [$id]));
    }

    public function removeFile(int $order, int $file): void
    {
        unset($this->orderFiles[$order][$file]);
        $this->orderFiles[$order] = array_values($this->orderFiles[$order] ?? []);
    }

    // ── المبلغ ───────────────────────────────────────────

    /**
     * الأرقام العربية ← لاتينية، وفواصل الآلاف والمسافات تُحذف («١٥٠٬٠٠٠» = 150000).
     *
     * ⚠️ **النقطة لا تُحذف**: «12.5» تصير 125 بلا أثرٍ ظاهر — فتبقى ويرفضها التحقق.
     */
    protected static function normalizeAmount(string $value): string
    {
        return preg_replace('/[\s,٬،]/u', '', ArabicDigits::toLatin(trim($value))) ?? '';
    }

    /**
     * المبلغ رقماً إن كان صالحاً — يقرؤه التفقيط الحيّ وشاشة التأكيد.
     * أبنية المحاكم: مجموع ما كُتب صالحاً من العناصر (يتحدّث مع الكتابة)، وnull قبل أول رقم.
     */
    public function amountValue(): ?int
    {
        // اتصالات: «المبلغ» المنصرف — ما يغطيه أمر الدفع، وصفرُه يُسقط أمر الدفع
        if ($this->ledger) {
            return self::parseAmount($this->expense);
        }

        if ($this->itemIds) {
            $values = array_filter(
                array_map(fn ($id) => $this->itemValue($id), $this->itemIds),
                fn ($v) => $v !== null
            );

            return $values ? array_sum($values) : null;
        }

        $digits = self::normalizeAmount($this->amount);

        return preg_match('/^\d{1,12}$/', $digits) ? (int) $digits : null;
    }

    // ── الخطوات ──────────────────────────────────────────

    protected function itemValue(int|string $id): ?int
    {
        return self::parseAmount((string) ($this->items[$id] ?? ''));
    }

    protected static function parseAmount(string $value): ?int
    {
        $digits = self::normalizeAmount($value);

        return preg_match('/^\d{1,12}$/', $digits) ? (int) $digits : null;
    }

    public function updatedExpense(): void
    {
        $this->updatedAmount();
    }

    /**
     * اتصالات: المرحَّل إلى هذا الشهر — رصيد ما قبله. للإدخال رصيد آخر شهر، وللتصحيح
     * رصيد ما قبل الشهر نفسه في السلسلة.
     */
    protected function carriedIn(FinanceAccount $account): int
    {
        return $this->editingId
            ? TelecomLedger::carriedInto($account, CarbonImmutable::parse($this->month))
            : TelecomLedger::closingBalance($account);
    }

    /**
     * ⚠️ **الرصيد لا يكون سالباً** (قرار العميل) — إدخالاً: المنصرف ≤ المرحَّل + الإيراد؛
     *    وتصحيحاً: السلسلة كلها بعد القيم الجديدة، والرسالة تسمّي أول شهرٍ يصير سالباً.
     */
    protected function guardNegative(FinanceAccount $account): void
    {
        $income  = (int) $this->income;
        $expense = (int) $this->expense;

        if (! $this->editingId) {
            $available = $this->carriedIn($account) + $income;
            if ($expense > $available) {
                throw ValidationException::withMessages([
                    'expense' => __('home.fin_expense_over_available', ['available' => number_format($available)]),
                ]);
            }

            return;
        }

        $negative = TelecomLedger::firstNegative($account, [$this->editingId => ['income' => $income, 'expense' => $expense]]);
        if ($negative) {
            throw ValidationException::withMessages([
                'expense' => __('home.fin_correction_negative', [
                    'month'   => FinanceCalendar::monthLabel($negative['month']),
                    'balance' => number_format($negative['balance']),
                ]),
            ]);
        }
    }

    /** المبلغ تغيّر عن الصفر ← يعود أمر الدفع إجبارياً */
    public function updatedAmount(): void
    {
        if ($this->amountValue() !== 0) {
            $this->noOrder = false;
        }
    }

    public function updatedItems(): void
    {
        $this->updatedAmount();
    }

    protected function rules(): array
    {
        $reason = $this->editingId ? ['reason' => ['required', 'string', 'min:5', 'max:1000']] : [];

        // أبنية المحاكم: كل عنصرٍ إلزامي (الصفر مسموح) — والإجمالي يُحسب بعد التحقق
        $amount = match (true) {
            $this->ledger => ['income' => ['required', 'regex:/^\d{1,12}$/'], 'expense' => ['required', 'regex:/^\d{1,12}$/']],
            (bool) $this->itemIds => ['items' => ['required', 'array'], 'items.*' => ['required', 'regex:/^\d{1,12}$/']],
            default => ['amount' => ['required', 'regex:/^\d{1,12}$/']],
        };

        if ($this->noOrder) {
            return $amount + $reason;
        }

        return $reason + $amount + [
            'orders'              => ['required', 'array', 'min:1', 'max:'.FinancePaymentOrder::MAX_PER_MONTH],
            // أرقام فقط (قرار المستخدمة) — بعد توحيد الأرقام العربية في validateEntry
            'orders.*.number'     => ['required', 'regex:/^\d{1,30}$/', 'distinct'],
            'orders.*.issued_on'  => ['required', 'date', 'before_or_equal:'.now(LocalTime::timezone())->toDateString()],
            'orderFiles'          => ['array'],
            // التصحيح: الحدّان على المحفوظ + الجديد معاً — في validateEntry
            'orderFiles.*'        => $this->editingId
                ? ['array']
                : ['required', 'array', 'min:1', 'max:'.FinanceAttachment::MAX_PER_ORDER],
            'orderFiles.*.*'      => FinanceAttachment::FILE_RULES,
        ];
    }

    protected function messages(): array
    {
        return [
            'amount.required'       => __('home.fin_amount_invalid'),
            'amount.regex'          => __('home.fin_amount_invalid'),
            'income.required'       => __('home.fin_amount_invalid'),
            'income.regex'          => __('home.fin_amount_invalid'),
            'expense.required'      => __('home.fin_amount_invalid'),
            'expense.regex'         => __('home.fin_amount_invalid'),
            'items.*.required'      => __('home.fin_item_required'),
            'items.*.regex'         => __('home.fin_amount_invalid'),
            'orders.*.number.regex' => __('home.fin_order_number_digits'),
            'orderFiles.*.required' => __('home.fin_files_required'),
            'orderFiles.*.min'      => __('home.fin_files_required'),
            'reason.required'       => __('home.fin_reason_required'),
            'reason.min'            => __('home.fin_reason_required'),
        ];
    }

    protected function validateEntry(): void
    {
        abort_unless(auth()->user()?->can($this->editingId ? 'finance.edit' : 'finance.create'), 403);
        abort_if($this->month === null, 404);

        $this->amount  = self::normalizeAmount($this->amount);
        $this->income  = self::normalizeAmount($this->income);
        $this->expense = self::normalizeAmount($this->expense);
        $this->reason  = trim($this->reason);

        // ⚠️ العناصر من `itemIds` المقفولة لا من مفاتيح ما وصل — مفتاحٌ مدسوس لا يُحفظ،
        //    وعنصرٌ حُذف مفتاحه من المتصفح يبقى مطلوباً
        if ($this->itemIds) {
            $this->items = collect($this->itemIds)
                ->mapWithKeys(fn ($id) => [$id => self::normalizeAmount((string) ($this->items[$id] ?? ''))])
                ->all();
        }
        $this->orders = array_map(fn (array $o) => [
            'id'        => isset($o['id']) && ctype_digit((string) $o['id']) ? (int) $o['id'] : null,
            'number'    => trim(ArabicDigits::toLatin((string) ($o['number'] ?? ''))),
            'issued_on' => (string) ($o['issued_on'] ?? ''),
        ], $this->orders);

        // أمرٌ بلا صور لا مفتاح له في المصفوفة — فيُفحص `required` على كل أمر
        foreach (array_keys($this->orders) as $i) {
            $this->orderFiles[$i] ??= [];
        }

        $this->validate();

        if ($this->itemIds) {
            $this->amount = (string) array_sum(array_map('intval', $this->items));
        }

        if ($this->ledger) {
            $this->amount = $this->expense;
            // فحصٌ مبكر للمراجعة — ويُعاد داخل معاملة الحفظ بعد قفل الحساب
            $this->guardNegative($this->account());
        }

        // ⚠️ في الإجراء لا في القالب: الخانة تظهر للصفر وحده، والقيمة تصل من المتصفح
        if ($this->noOrder && (int) $this->amount !== 0) {
            throw ValidationException::withMessages(['noOrder' => __('home.fin_no_order_zero_only')]);
        }

        if ($this->editingId && ! $this->noOrder) {
            // ⚠️ `kept` يصل من المتصفح: يبقى منه ما هو صورةٌ فعلاً لأمر هذا الصفّ — وإلا عدّ
            //    معرّفٌ مدسوس صورةً فمرّ أمرٌ بلا صورة
            $owned = FinanceAttachment::query()
                ->whereHas('paymentOrder', fn ($q) => $q->where('finance_month_id', $this->editingId))
                ->get(['id', 'finance_payment_order_id'])
                ->groupBy('finance_payment_order_id');

            foreach ($this->orders as $i => $order) {
                $ids = $owned->get($order['id'] ?? 0)?->pluck('id')->all() ?? [];
                $this->kept[$i] = array_values(array_intersect(array_map('intval', (array) ($this->kept[$i] ?? [])), $ids));
            }

            $errors = [];
            foreach (array_keys($this->orders) as $i) {
                $count = $this->imageCount($i);
                if ($count < 1) {
                    $errors["orderFiles.$i"] = __('home.fin_files_required');
                } elseif ($count > FinanceAttachment::MAX_PER_ORDER) {
                    $errors["orderFiles.$i"] = __('home.fin_images_max', ['max' => FinanceAttachment::MAX_PER_ORDER]);
                }
            }
            if ($errors) {
                throw ValidationException::withMessages($errors);
            }
        }
    }

    public function review(): void
    {
        $this->validateEntry();
        $this->reviewing = true;
    }

    public function backToForm(): void
    {
        $this->reviewing = false;
    }

    public function save(): void
    {
        if ($this->editingId) {
            $this->saveCorrection();

            return;
        }

        $this->validateEntry();

        $account = $this->account();
        $month   = CarbonImmutable::parse($this->month);

        try {
            $saved = DB::transaction(function () use ($account, $month) {
                // القفل على صفّ الحساب يرتّب الحفظ المتزامن لنفس الحساب
                FinanceAccount::whereKey($account->id)->lockForUpdate()->first();

                if (! FinanceCalendar::nextMonth($account)->equalTo($month)) {
                    return false;
                }

                if ($this->ledger) {
                    // ⚠️ يُعاد هنا تحت القفل: تصحيحٌ لشهرٍ سابق في الأثناء يغيّر المرحَّل
                    $this->guardNegative($account->fresh());
                }

                $record = FinanceMonth::create([
                    'finance_account_id' => $account->id,
                    'month'              => $month->toDateString(),
                    'amount'             => (int) $this->amount,
                    'income'             => $this->ledger ? (int) $this->income : null,
                    'created_by'         => auth()->id(),
                ]);

                foreach ($this->items as $itemId => $value) {
                    $record->items()->create(['court_building_item_id' => $itemId, 'amount' => (int) $value]);
                }

                foreach ($this->noOrder ? [] : array_values($this->orders) as $i => $order) {
                    $paymentOrder = $record->paymentOrders()->create([
                        'number'    => $order['number'],
                        'issued_on' => $order['issued_on'],
                        'position'  => $i + 1,
                    ]);

                    foreach ($this->orderFiles[$i] as $file) {
                        $path = $file->store(FinanceAttachment::DIRECTORY, FinanceAttachment::DISK);
                        $this->storedPaths[] = $path;

                        $paymentOrder->attachments()->create([
                            'path'          => $path,
                            'original_name' => $file->getClientOriginalName(),
                            'uploaded_by'   => auth()->id(),
                        ]);
                    }
                }

                return true;
            });
        } catch (\Throwable $e) {
            Storage::disk(FinanceAttachment::DISK)->delete($this->storedPaths);
            $this->storedPaths = [];

            throw $e;
        }

        if (! $saved) {
            Flux::toast(variant: 'danger', text: __('home.fin_entry_stale'));
            $this->redirect(route($account->pageRoute()), navigate: true);

            return;
        }

        Flux::toast(variant: 'success', text: __('home.fin_entry_saved', ['month' => FinanceCalendar::monthLabel($month)]));

        $this->redirect(
            route($account->pageRoute(), ['year' => FinanceCalendar::fiscalYearOf($month)]),
            navigate: true
        );
    }

    /**
     * حفظ التصحيح: المبلغ · الأوامر (تُحدَّث وتُضاف وتُحذف) · الصور (المُزالة تُحذف بالموديل
     * فيُحذف ملفّها بعد الاعتماد، والجديدة تُخزَّن) — ثم السجلّ قبل/بعد بسببه.
     *
     * ⚠️ معرّف الأمر والصورة يصلان من المتصفح: ما ليس لهذا الشهر يُهمَل (الأمر يُعامل جديداً).
     * ⚠️ تصحيحٌ لا يغيّر شيئاً يُرفض — وإلا امتلأ السجلّ بأسبابٍ بلا تغيير.
     */
    protected function saveCorrection(): void
    {
        $this->validateEntry();

        $account = $this->account();
        $month   = CarbonImmutable::parse($this->month);

        try {
            $result = DB::transaction(function () use ($account, $month) {
                // قفل الحساب أولاً (ترتيب الإدخال والحذف نفسه) — فحص السالب يقرأ السلسلة كلها
                FinanceAccount::whereKey($account->id)->lockForUpdate()->first();

                $record = FinanceMonth::whereKey($this->editingId)
                    ->where('finance_account_id', $account->id)
                    ->lockForUpdate()
                    ->first();

                if ($record === null) {
                    return 'stale';
                }

                $record->load('paymentOrders.attachments');
                $before  = FinanceHistory::snapshot($record);
                $added   = 0;
                $removed = 0;

                if ($this->ledger) {
                    $this->guardNegative($account->fresh());
                }

                $record->update([
                    'amount' => (int) $this->amount,
                    'income' => $this->ledger ? (int) $this->income : $record->income,
                ]);

                // العناصر: قيمٌ لعناصر الشهر نفسه (`itemIds` من الصفّ عند الفتح)
                foreach ($this->items as $itemId => $value) {
                    // ⚠️ لا عبر علاقة `items()` — فيها join، وsqlite لا يقبل UPDATE بـjoin
                    FinanceMonthItem::where('finance_month_id', $record->id)
                        ->where('court_building_item_id', $itemId)
                        ->update(['amount' => (int) $value]);
                }

                $existing = $record->paymentOrders->keyBy('id');
                $wanted   = $this->noOrder ? [] : array_values($this->orders);
                $keepIds  = array_filter(array_column($wanted, 'id'), fn ($id) => $existing->has($id));

                foreach ($existing->except($keepIds) as $order) {
                    $removed += $order->attachments->count();
                    $order->delete();
                }

                foreach ($wanted as $i => $data) {
                    $order = $existing->get($data['id'] ?? 0);

                    if ($order && in_array($order->id, $keepIds, true)) {
                        $order->update([
                            'number'    => $data['number'],
                            'issued_on' => $data['issued_on'],
                            'position'  => $i + 1,
                        ]);

                        foreach ($order->attachments->whereNotIn('id', $this->kept[$i] ?? []) as $attachment) {
                            $attachment->delete();
                            $removed++;
                        }
                    } else {
                        $order = $record->paymentOrders()->create([
                            'number'    => $data['number'],
                            'issued_on' => $data['issued_on'],
                            'position'  => $i + 1,
                        ]);
                    }

                    foreach ($this->orderFiles[$i] ?? [] as $file) {
                        $path = $file->store(FinanceAttachment::DIRECTORY, FinanceAttachment::DISK);
                        $this->storedPaths[] = $path;

                        $order->attachments()->create([
                            'path'          => $path,
                            'original_name' => $file->getClientOriginalName(),
                            'uploaded_by'   => auth()->id(),
                        ]);
                        $added++;
                    }
                }

                $after = FinanceHistory::snapshot($record->fresh());

                if ($before === $after && ! $added && ! $removed) {
                    // الاستثناء يُرجِع المعاملة كلها (ترتيبٌ أُعيد كتابته بالقيمة نفسها)
                    $this->reviewing = false;

                    throw ValidationException::withMessages(['reason' => __('home.fin_correction_unchanged')]);
                }

                FinanceHistory::record(
                    FinanceHistory::EVENT_CORRECTED, $account, $month, $this->reason,
                    $before, $after, $added, $removed,
                );

                return 'saved';
            });
        } catch (\Throwable $e) {
            Storage::disk(FinanceAttachment::DISK)->delete($this->storedPaths);
            $this->storedPaths = [];

            throw $e;
        }

        if ($result === 'stale') {
            Flux::toast(variant: 'danger', text: __('home.fin_correction_stale'));
            $this->redirect(route($account->pageRoute()), navigate: true);

            return;
        }

        Flux::toast(variant: 'success', text: __('home.fin_correction_saved', ['month' => FinanceCalendar::monthLabel($month)]));

        $this->redirect(
            route($account->pageRoute(), ['year' => FinanceCalendar::fiscalYearOf($month)]),
            navigate: true
        );
    }

    /**
     * مربع الحساب في الفورم (يتحدّث مع الكتابة): المرحَّل + الإيراد = المتاح − المنصرف = الرصيد.
     * القيمة غير الصالحة صفرٌ في العرض وحده — التحقق يرفضها عند المراجعة.
     */
    protected function ledgerCalc(FinanceAccount $account): array
    {
        $month   = CarbonImmutable::parse($this->month);
        $carried = $this->carriedIn($account);
        $income  = self::parseAmount($this->income) ?? 0;
        $expense = self::parseAmount($this->expense) ?? 0;

        $previous = $month->subMonth();

        return [
            'carriedFrom' => $previous->lessThan(FinanceCalendar::firstMonth())
                ? __('home.fin_opening_balance')
                : FinanceCalendar::monthLabel($previous),
            'carried'     => $carried,
            'income'      => $income,
            'available'   => $carried + $income,
            'expense'     => $expense,
            'balance'     => $carried + $income - $expense,
            'nextLabel'   => FinanceCalendar::monthLabel($month->addMonth()),
        ];
    }

    /** المبلغ «بعيد» عن الشهر السابق إن تجاوز أضعافه أو نزل عن جزئه هذا */
    public const AMOUNT_JUMP_FACTOR = 3;

    /**
     * تنبيهات شاشة المراجعة — **تنبّه ولا تمنع**: لكلٍّ منها حالةٌ صحيحة نادرة،
     * والقرار للمُدخِل وهو يرى الأرقام أمامه قبل القفل.
     *
     *   - رقم أمر دفع سُجِّل من قبل (أي حساب) — الأرجح الأمرُ نفسه مرتين.
     *   - مبلغٌ بعيد عن الشهر السابق — صفرٌ زائد أو ناقص يفوت العين.
     *   - أمر دفع أقدم من الشهر بأكثر من شهر — تاريخٌ كُتب خطأً.
     */
    protected function reviewWarnings(FinanceAccount $account, ?int $amount): array
    {
        if (! $this->reviewing || $this->month === null) {
            return [];
        }

        $month    = CarbonImmutable::parse($this->month);
        $warnings = [];

        $orders  = $this->noOrder ? [] : $this->orders;
        $numbers = array_values(array_filter(array_column($orders, 'number')));
        if ($numbers) {
            FinancePaymentOrder::whereIn('number', $numbers)
                ->when($this->editingId, fn ($q) => $q->where('finance_month_id', '!=', $this->editingId))
                ->with('month.account')
                ->get()
                ->each(function (FinancePaymentOrder $order) use (&$warnings) {
                    $warnings[] = __('home.fin_warn_duplicate_order', [
                        'number'  => $order->number,
                        'account' => $order->month->account->label(),
                        'month'   => FinanceCalendar::monthLabel(CarbonImmutable::parse($order->month->month)),
                    ]);
                });
        }

        // ⚠️ نطاقٌ لا مساواة: العمود المصبوب `'date'` يُكتب `'Y-m-d 00:00:00'` على sqlite
        $previous = FinanceMonth::where('finance_account_id', $account->id)
            ->where('month', '>=', $month->subMonth()->toDateString())
            ->where('month', '<', $month->toDateString())
            ->value('amount');

        if ($amount !== null && $previous > 0) {
            $context = [
                'month'  => FinanceCalendar::monthLabel($month->subMonth()),
                'amount' => number_format($previous),
                'factor' => self::AMOUNT_JUMP_FACTOR,
            ];

            if ($amount > $previous * self::AMOUNT_JUMP_FACTOR) {
                $warnings[] = __('home.fin_warn_amount_high', $context);
            } elseif ($amount * self::AMOUNT_JUMP_FACTOR < $previous) {
                $warnings[] = __('home.fin_warn_amount_low', $context);
            }
        }

        $oldest = $month->subMonth()->toDateString();
        foreach ($orders as $order) {
            if (($order['issued_on'] ?? '') !== '' && $order['issued_on'] < $oldest) {
                $warnings[] = __('home.fin_warn_old_order', [
                    'number' => $order['number'],
                    'date'   => $order['issued_on'],
                    'month'  => FinanceCalendar::monthLabel($month),
                ]);
            }
        }

        return $warnings;
    }

    public function render()
    {
        $account = $this->account();
        $amount  = $this->amountValue();
        $record  = $this->editingId
            ? FinanceMonth::with('account', 'paymentOrders.attachments', 'items')->find($this->editingId)
            : null;

        return view('livewire.finance.month-entry', [
            'account'     => $account,
            'monthLabel'  => $this->month ? FinanceCalendar::monthLabel(CarbonImmutable::parse($this->month)) : null,
            'fiscalLabel' => $this->month
                ? FinanceCalendar::fiscalYearLabel(FinanceCalendar::fiscalYearOf(CarbonImmutable::parse($this->month)))
                : null,
            'nextLabel'   => FinanceCalendar::monthLabel(FinanceCalendar::nextMonth($account)),
            'amountValue' => $amount,
            'amountWords' => $amount !== null ? AmountInWords::pounds($amount) : null,
            'warnings'    => $this->reviewWarnings($account, $amount),
            'record'      => $record,
            'needsOpening' => $account->needsOpening(),
            'ledgerCalc'  => $this->ledger && $this->month ? $this->ledgerCalc($account) : null,
            // بترتيب `itemIds` (ترتيب العناصر وقت فتح الفورم)
            'itemRows'    => $this->itemIds
                ? CourtBuildingItem::whereIn('id', $this->itemIds)->get()->keyBy('id')
                : collect(),
            'keptFiles'   => $record
                ? $record->paymentOrders->flatMap->attachments->keyBy('id')
                : collect(),
        ])->title(__($record ? 'home.fin_correction_title' : 'home.fin_entry_title', ['account' => $account->label()]));
    }
}
