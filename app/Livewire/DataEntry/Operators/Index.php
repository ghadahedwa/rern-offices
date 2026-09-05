<?php

namespace App\Livewire\DataEntry\Operators;

use App\Livewire\Concerns\WithPerPage;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\AttendanceDay;
use App\Models\DataEntryAssignment;
use App\Models\DataEntryOperator;
use App\Support\ArabicText;
use App\Support\DataEntryScope;
use App\Support\WorkingDays;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * قائمة مدخلي البيانات — والتسكين والنقل وإنهاء الخدمة من هنا.
 *
 * ⚠️ **لا حذف بل أرشفة** (قرار العميل): إنهاء الخدمة يُغلق التسكين فيخرج المدخل من
 *    الأعداد الحالية ويبقى في تقارير الفترات التي خدم فيها. والحذف النهائي متاح
 *    لصاحب `data-entry.delete` **ولمن لا سجل حضور له فقط** — لتصحيح إدخالٍ خاطئ
 *    لا لطيّ تاريخ موظف.
 * ⚠️ والنطاق محافظة: يمرّ كل استعلام من `DataEntryScope` — الصفوف والمنسدلات معاً.
 */
#[Layout('layouts.app')]
#[Title('مدخلو البيانات')]
class Index extends Component
{
    use WithPagination;
    use WithPerPage;
    use WithTableSorting;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'gov', except: '')]
    public string $governorate = '';

    #[Url(as: 'office', except: '')]
    public string $office = '';

    /** in_service | ended | all */
    #[Url(as: 'status', except: 'in_service')]
    public string $status = 'in_service';

    // ── نقل المدخل ──
    public bool $showTransfer = false;
    public ?int $transferOperatorId = null;
    public string $transferOperatorName = '';
    public string $transferGovernorate = '';
    public string $transferOffice = '';
    public string $transferDate = '';

    // ── إنهاء الخدمة ──
    public bool $showEnd = false;
    public ?int $endOperatorId = null;
    public string $endOperatorName = '';
    public string $endDate = '';

    // ── إعادة التسكين ──
    public bool $showReassign = false;
    public ?int $reassignOperatorId = null;
    public string $reassignOperatorName = '';
    public string $reassignGovernorate = '';
    public string $reassignOffice = '';
    public string $reassignDate = '';

    // ── الحذف ──
    public bool $showDelete = false;
    public ?int $deletingId = null;
    public string $deletingLabel = '';
    public string $deletingWarning = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('data-entry.index'), 403);
    }

    private function guard(string $ability): void
    {
        abort_unless(Auth::user()?->can($ability), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedGovernorate(): void
    {
        // المقر المختار قد لا ينتمي للمحافظة الجديدة — فيُصفَّر بدل أن يُخرج شاشة فارغة
        $this->office = '';
        $this->resetPage();
    }

    public function updatedOffice(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    // المقر المختار قد لا ينتمي للمحافظة الجديدة — يُصفَّر بدل أن يُحفظ تسكينٌ في غير موضعه
    public function updatedTransferGovernorate(): void
    {
        $this->transferOffice = '';
    }

    public function updatedReassignGovernorate(): void
    {
        $this->reassignOffice = '';
    }

    /** الترتيب الافتراضي: أبجدي بالاسم — القائمة تُقرأ بحثاً عن شخص. */
    protected function defaultOrder(Builder $query): Builder
    {
        return $query->orderBy('data_entry_operators.name');
    }

    /**
     * ⚠️ قائمة بيضاء: اسم العمود يأتي من الرابط ولا يُمرَّر لـorderBy قبل المرور بها.
     *    و`current_started_on` عمودٌ محسوب في render (تاريخ بدء التسكين المفتوح).
     */
    protected function sortableColumns(): array
    {
        return [
            'name'       => 'data_entry_operators.name',
            'phone'      => 'data_entry_operators.phone',
            'started_on' => 'current_started_on',
        ];
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->governorate !== ''
            || $this->office !== ''
            || $this->status !== 'in_service'
            || $this->isCustomSorted();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'governorate', 'office');
        $this->status = 'in_service';
        $this->resetSort();
        $this->resetPage();
    }

    public function canEdit(): bool
    {
        return (bool) Auth::user()?->can('data-entry.edit');
    }

    public function canDelete(): bool
    {
        return (bool) Auth::user()?->can('data-entry.delete');
    }

    // ── النقل ───────────────────────────────────────────

    public function askTransfer(int $id): void
    {
        $this->guard('data-entry.edit');

        $operator = $this->scopedOperator($id);

        $this->transferOperatorId   = $operator->id;
        $this->transferOperatorName = $operator->name;
        // النقل داخل المحافظة هو الغالب، فتُفتح على محافظة المقر الحالي
        $this->transferGovernorate  = (string) ($operator->currentAssignment?->office?->governorate_id ?? '');
        $this->transferOffice       = '';
        $this->transferDate         = WorkingDays::today()->toDateString();
        $this->resetValidation();
        $this->showTransfer         = true;
    }

    public function transfer(): void
    {
        $this->guard('data-entry.edit');

        // ⚠️ لا يُنفَّذ إجراءٌ لم يُطلب تأكيده — النداء يصل في طلب مستقل
        if (! $this->showTransfer || ! $this->transferOperatorId) {
            return;
        }

        $this->validate([
            'transferOffice' => ['required', 'integer'],
            'transferDate'   => ['required', 'date'],
        ]);

        // ⚠️ المقر يصل من العميل — فيُفحص على النطاق لا على وجوده فقط
        if (! DataEntryScope::allowsOffice((int) $this->transferOffice)) {
            $this->addError('transferOffice', __('home.de_operator_office_out_of_scope'));

            return;
        }

        $operator = $this->scopedOperator($this->transferOperatorId);
        $current  = $operator->assignments()->whereNull('ended_on')->latest('started_on')->first();

        if ($current && $this->transferDate <= $current->started_on->toDateString()) {
            $this->addError('transferDate', __('home.de_operator_transfer_date_invalid'));

            return;
        }

        DB::transaction(function () use ($operator, $current) {
            // التسكين السابق ينتهي في اليوم السابق للنقل — فلا يوم بمقرّين
            $current?->update([
                'ended_on'   => \Carbon\CarbonImmutable::parse($this->transferDate)->subDay()->toDateString(),
                'end_reason' => DataEntryAssignment::REASON_TRANSFER,
            ]);

            $operator->assignments()->create([
                'office_id'  => (int) $this->transferOffice,
                'started_on' => $this->transferDate,
            ]);
        });

        $this->reset('showTransfer', 'transferOperatorId', 'transferOperatorName', 'transferGovernorate', 'transferOffice', 'transferDate');
        Flux::toast(variant: 'success', text: __('home.de_operator_transferred'));
    }

    // ── إنهاء الخدمة ────────────────────────────────────

    public function askEnd(int $id): void
    {
        $this->guard('data-entry.edit');

        $operator = $this->scopedOperator($id);

        $this->endOperatorId   = $operator->id;
        $this->endOperatorName = $operator->name;
        $this->endDate         = WorkingDays::today()->toDateString();
        $this->resetValidation();
        $this->showEnd         = true;
    }

    public function endService(): void
    {
        $this->guard('data-entry.edit');

        if (! $this->showEnd || ! $this->endOperatorId) {
            return;
        }

        $this->validate(['endDate' => ['required', 'date']]);

        $operator = $this->scopedOperator($this->endOperatorId);
        $current  = $operator->assignments()->whereNull('ended_on')->latest('started_on')->first();

        if (! $current) {
            $this->reset('showEnd', 'endOperatorId', 'endOperatorName', 'endDate');
            Flux::toast(variant: 'warning', text: __('home.de_operator_already_ended'));

            return;
        }

        if ($this->endDate < $current->started_on->toDateString()) {
            $this->addError('endDate', __('home.de_operator_end_date_invalid'));

            return;
        }

        $current->update([
            'ended_on'   => $this->endDate,
            'end_reason' => DataEntryAssignment::REASON_LEFT,
        ]);

        $this->reset('showEnd', 'endOperatorId', 'endOperatorName', 'endDate');
        Flux::toast(variant: 'success', text: __('home.de_operator_ended'));
    }

    // ── إعادة التسكين ───────────────────────────────────

    public function askReassign(int $id): void
    {
        $this->guard('data-entry.edit');

        $operator = $this->scopedOperator($id);

        $this->reassignOperatorId   = $operator->id;
        $this->reassignOperatorName = $operator->name;
        $this->reassignGovernorate  = (string) (
            $operator->assignments()->orderByDesc('started_on')->first()?->office?->governorate_id ?? ''
        );
        $this->reassignOffice       = '';
        $this->reassignDate         = WorkingDays::today()->toDateString();
        $this->resetValidation();
        $this->showReassign         = true;
    }

    /**
     * عودة مدخل بعد انقطاع — أو تصحيح إنهاء خدمةٍ وقع بالخطأ.
     *
     * ⚠️ تسكينٌ جديد لا تعديلٌ للقديم: مدة الانقطاع نفسها معلومة تخصّ التقارير،
     *    وإعادة فتح التسكين القديم تجعل أيام الانقطاع حضوراً بالاشتقاق.
     */
    public function reassign(): void
    {
        $this->guard('data-entry.edit');

        // ⚠️ لا يُنفَّذ إجراءٌ لم يُطلب تأكيده — النداء يصل في طلب مستقل
        if (! $this->showReassign || ! $this->reassignOperatorId) {
            return;
        }

        $this->validate([
            'reassignOffice' => ['required', 'integer'],
            'reassignDate'   => ['required', 'date'],
        ]);

        if (! DataEntryScope::allowsOffice((int) $this->reassignOffice)) {
            $this->addError('reassignOffice', __('home.de_operator_office_out_of_scope'));

            return;
        }

        $operator = $this->scopedOperator($this->reassignOperatorId);

        if ($operator->isInService()) {
            $this->reset('showReassign', 'reassignOperatorId', 'reassignOperatorName', 'reassignGovernorate', 'reassignOffice', 'reassignDate');
            Flux::toast(variant: 'warning', text: __('home.de_operator_already_in_service'));

            return;
        }

        $last = $operator->assignments()->orderByDesc('ended_on')->first();

        // ⚠️ التاريخ يتجاوز نهاية آخر تسكين قطعاً — وإلا تداخلت المدد فعُدّ اليوم مرتين
        if ($last?->ended_on && $this->reassignDate <= $last->ended_on->toDateString()) {
            $this->addError('reassignDate', __('home.de_operator_reassign_date_invalid'));

            return;
        }

        $operator->assignments()->create([
            'office_id'  => (int) $this->reassignOffice,
            'started_on' => $this->reassignDate,
        ]);

        $this->reset('showReassign', 'reassignOperatorId', 'reassignOperatorName', 'reassignGovernorate', 'reassignOffice', 'reassignDate');
        Flux::toast(variant: 'success', text: __('home.de_operator_reassigned'));
    }

    // ── الحذف ───────────────────────────────────────────

    public function askDelete(int $id): void
    {
        $this->guard('data-entry.delete');

        $operator = $this->scopedOperator($id);

        $this->deletingId      = $operator->id;
        $this->deletingLabel   = $operator->name;
        $this->deletingWarning = __('home.de_operator_delete_warning');
        $this->showDelete      = true;
    }

    public function deleteRow(): void
    {
        $this->guard('data-entry.delete');

        if (! $this->showDelete || ! $this->deletingId) {
            return;
        }

        $operator = $this->scopedOperator($this->deletingId);

        // ⚠️ الحذف لتصحيح إدخالٍ خاطئ لا لطيّ تاريخ موظف: مَن له سجل حضور
        //    تُنهى خدمته ولا يُحذف — وإلا اختفى غيابه من تقارير شهرٍ مضى.
        if (AttendanceDay::forOperator($operator)->exists()) {
            $this->reset('showDelete', 'deletingId', 'deletingLabel', 'deletingWarning');
            Flux::toast(variant: 'danger', text: __('home.de_operator_has_attendance'));

            return;
        }

        $operator->delete();

        $this->reset('showDelete', 'deletingId', 'deletingLabel', 'deletingWarning');
        Flux::toast(variant: 'success', text: __('home.de_operator_deleted'));
    }

    /** ⚠️ كل معرّف يصل من العميل يُقرأ عبر النطاق — لا `findOrFail` مجرَّدة. */
    private function scopedOperator(int $id): DataEntryOperator
    {
        return DataEntryScope::applyToOperators(DataEntryOperator::whereKey($id))->firstOrFail();
    }

    public function render()
    {
        $governorateId = ctype_digit($this->governorate) ? (int) $this->governorate : null;
        $officeId      = ctype_digit($this->office) ? (int) $this->office : null;

        // تاريخ بدء التسكين المفتوح كعمود محسوب — ليُرتَّب به بلا تحميل العلاقات كلها
        $currentStart = DataEntryAssignment::query()
            ->select('started_on')
            ->whereColumn('operator_id', 'data_entry_operators.id')
            ->whereNull('ended_on')
            ->orderByDesc('started_on')
            ->limit(1);

        $operators = DataEntryScope::applyToOperators(DataEntryOperator::query())
            ->select('data_entry_operators.*')
            ->selectSub($currentStart, 'current_started_on')
            ->with(['currentAssignment.office.governorate'])
            ->when($this->search, fn ($q) => $q->where(function ($inner) {
                $inner->whereRaw(
                    ArabicText::sqlNormalize('name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                )->orWhere('phone', 'like', '%'.$this->search.'%');
            }))
            // الفلترة بالمقر/المحافظة على **التسكين الحالي** — سؤال المستخدم «مَن عندي الآن؟»
            ->when($officeId, fn ($q) => $q->whereHas('currentAssignment', fn ($a) => $a->where('office_id', $officeId)))
            ->when(
                $governorateId && ! $officeId,
                fn ($q) => $q->whereHas('currentAssignment.office', fn ($o) => $o->where('governorate_id', $governorateId))
            )
            ->when($this->status === 'in_service', fn ($q) => $q->inService())
            ->when($this->status === 'ended', fn ($q) => $q->whereDoesntHave('assignments', fn ($a) => $a->whereNull('ended_on')));

        // ⚠️ مُرجِّح ثابت: بلا ترتيبٍ حاسم يتبدّل موضع الصفوف المتساوية بين الصفحتين
        $operators = $this->applySorting($operators, 'data_entry_operators.id')
            ->paginate($this->perPage());

        return view('livewire.data-entry.operators.index', [
            'operators'    => $operators,
            'governorates' => DataEntryScope::governorateOptions(),
            'offices'      => DataEntryScope::officeOptions($governorateId),
            // ⚠️ قوائم المودالين مستقلة عن فلتر الشاشة: النقل قد يكون إلى محافظة
            //    أخرى داخل النطاق، وفلترُ الشاشة سؤالٌ آخر (مَن أعرض الآن؟).
            'transferOffices' => DataEntryScope::officeOptions(
                ctype_digit($this->transferGovernorate) ? (int) $this->transferGovernorate : null
            ),
            'reassignOffices' => DataEntryScope::officeOptions(
                ctype_digit($this->reassignGovernorate) ? (int) $this->reassignGovernorate : null
            ),
        ]);
    }
}
