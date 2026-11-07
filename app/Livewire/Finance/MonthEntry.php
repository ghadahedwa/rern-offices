<?php

namespace App\Livewire\Finance;

use App\Models\FinanceAccount;
use App\Models\FinanceAttachment;
use App\Models\FinanceMonth;
use App\Models\FinancePaymentOrder;
use App\Support\ArabicDigits;
use App\Support\Finance\AmountInWords;
use App\Support\Finance\FinanceCalendar;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
 * ⚠️ **الشهر يُقفل لحظة حفظه** — لا تعديل هنا. التصحيح في المرحلة (ج) بـ`finance.edit`.
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

    /** مسارات خُزِّنت في هذا الحفظ — تُحذف إن تراجعت المعاملة */
    protected array $storedPaths = [];

    public function mount(string $account): void
    {
        abort_unless(auth()->user()?->can('finance.create'), 403);

        $model = FinanceAccount::bySlug($account);
        abort_unless($model->acceptsEntry(), 404);

        $this->accountKey = $model->key;

        $next = FinanceCalendar::nextMonth($model);
        $this->month = FinanceCalendar::isDue($next) ? $next->toDateString() : null;

        $this->orders     = [['number' => '', 'issued_on' => '']];
        $this->orderFiles = [[]];
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

        $this->orders[]     = ['number' => '', 'issued_on' => ''];
        $this->orderFiles[] = [];
    }

    public function removeOrder(int $index): void
    {
        if (count($this->orders) <= 1 || ! isset($this->orders[$index])) {
            return;
        }

        unset($this->orders[$index], $this->orderFiles[$index], $this->picked[$index]);
        $this->orders     = array_values($this->orders);
        $this->orderFiles = array_values($this->orderFiles);
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
                if (count($this->orderFiles[$index] ?? []) >= FinanceAttachment::MAX_PER_ORDER) {
                    $overflow = true;
                    continue;
                }
                $this->orderFiles[$index][] = $file;
            }

            $messages = [];
            if ($rejected) {
                $messages[] = __('home.wh_attachment_rejected', ['names' => implode('، ', $rejected)]);
            }
            if ($overflow) {
                $messages[] = __('home.wh_attachments_max', ['max' => FinanceAttachment::MAX_PER_ORDER]);
            }
            if ($messages) {
                $this->addError("orderFiles.$index", implode(' — ', $messages));
            }
        }

        $this->picked = [];
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

    /** المبلغ رقماً إن كان صالحاً — يقرؤه التفقيط الحيّ وشاشة التأكيد. */
    public function amountValue(): ?int
    {
        $digits = self::normalizeAmount($this->amount);

        return preg_match('/^\d{1,12}$/', $digits) ? (int) $digits : null;
    }

    // ── الخطوات ──────────────────────────────────────────

    protected function rules(): array
    {
        return [
            'amount'              => ['required', 'regex:/^\d{1,12}$/'],
            'orders'              => ['required', 'array', 'min:1', 'max:'.FinancePaymentOrder::MAX_PER_MONTH],
            'orders.*.number'     => ['required', 'string', 'max:100', 'distinct'],
            'orders.*.issued_on'  => ['required', 'date', 'before_or_equal:'.now(LocalTime::timezone())->toDateString()],
            'orderFiles'          => ['array'],
            'orderFiles.*'        => ['required', 'array', 'min:1', 'max:'.FinanceAttachment::MAX_PER_ORDER],
            'orderFiles.*.*'      => FinanceAttachment::FILE_RULES,
        ];
    }

    protected function messages(): array
    {
        return [
            'amount.required'       => __('home.fin_amount_invalid'),
            'amount.regex'          => __('home.fin_amount_invalid'),
            'orderFiles.*.required' => __('home.fin_files_required'),
            'orderFiles.*.min'      => __('home.fin_files_required'),
        ];
    }

    protected function validateEntry(): void
    {
        abort_unless(auth()->user()?->can('finance.create'), 403);
        abort_if($this->month === null, 404);

        $this->amount = self::normalizeAmount($this->amount);
        $this->orders = array_map(fn (array $o) => [
            'number'    => trim(ArabicDigits::toLatin((string) ($o['number'] ?? ''))),
            'issued_on' => (string) ($o['issued_on'] ?? ''),
        ], $this->orders);

        // أمرٌ بلا صور لا مفتاح له في المصفوفة — فيُفحص `required` على كل أمر
        foreach (array_keys($this->orders) as $i) {
            $this->orderFiles[$i] ??= [];
        }

        $this->validate();
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

                $record = FinanceMonth::create([
                    'finance_account_id' => $account->id,
                    'month'              => $month->toDateString(),
                    'amount'             => (int) $this->amount,
                    'created_by'         => auth()->id(),
                ]);

                foreach (array_values($this->orders) as $i => $order) {
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

    public function render()
    {
        $account = $this->account();
        $amount  = $this->amountValue();

        return view('livewire.finance.month-entry', [
            'account'     => $account,
            'monthLabel'  => $this->month ? FinanceCalendar::monthLabel(CarbonImmutable::parse($this->month)) : null,
            'nextLabel'   => FinanceCalendar::monthLabel(FinanceCalendar::nextMonth($account)),
            'amountValue' => $amount,
            'amountWords' => $amount !== null ? AmountInWords::pounds($amount) : null,
        ])->title(__('home.fin_entry_title', ['account' => $account->label()]));
    }
}
