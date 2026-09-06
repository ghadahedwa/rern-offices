<?php

namespace App\Livewire\Contractors;

use App\Livewire\Concerns\WithPerPage;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\AttendanceDay;
use App\Models\ContractorAssignment;
use App\Models\Contractor;
use App\Models\OfficeType;
use App\Models\Profession;
use App\Support\ArabicText;
use App\Support\ContractorScope;
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
 * قائمة العاملين بالتعاقد — والتسكين والنقل وإنهاء الخدمة من هنا.
 *
 * ⚠️ **لا حذف بل أرشفة** (قرار العميل): إنهاء الخدمة يُغلق التسكين فيخرج المدخل من
 *    الأعداد الحالية ويبقى في تقارير الفترات التي خدم فيها. والحذف النهائي متاح
 *    لصاحب `contractors.delete` **ولمن لا سجل حضور له فقط** — لتصحيح إدخالٍ خاطئ
 *    لا لطيّ تاريخ موظف.
 * ⚠️ والنطاق محافظة: يمرّ كل استعلام من `ContractorScope` — الصفوف والمنسدلات معاً.
 */
#[Layout('layouts.app')]
#[Title('العاملون بالتعاقد')]
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

    #[Url(as: 'type', except: '')]
    public string $officeType = '';

    #[Url(as: 'prof', except: '')]
    public string $profession = '';

    /** in_service | ended | all */
    #[Url(as: 'status', except: 'in_service')]
    public string $status = 'in_service';

    // ── نقل المدخل ──
    public bool $showTransfer = false;
    public ?int $transferContractorId = null;
    public string $transferContractorName = '';
    public string $transferGovernorate = '';
    public string $transferOffice = '';
    public string $transferDate = '';

    // ── إنهاء الخدمة ──
    public bool $showEnd = false;
    public ?int $endContractorId = null;
    public string $endContractorName = '';
    public string $endDate = '';

    // ── إعادة التسكين ──
    public bool $showReassign = false;
    public ?int $reassignContractorId = null;
    public string $reassignContractorName = '';
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
        abort_unless(Auth::user()?->can('contractors.index'), 403);
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

    public function updatedOfficeType(): void
    {
        // المقر المختار قد لا يكون من النوع الجديد — فيُصفَّر بدل أن يُخرج شاشة فارغة
        $this->office = '';
        $this->resetPage();
    }

    public function updatedProfession(): void
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
        return $query->orderBy('contractors.name');
    }

    /**
     * ⚠️ قائمة بيضاء: اسم العمود يأتي من الرابط ولا يُمرَّر لـorderBy قبل المرور بها.
     *    و`current_started_on` عمودٌ محسوب في render (تاريخ بدء التسكين المفتوح).
     */
    protected function sortableColumns(): array
    {
        return [
            'name'       => 'contractors.name',
            'phone'      => 'contractors.phone',
            'started_on' => 'current_started_on',
        ];
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->governorate !== ''
            || $this->office !== ''
            || $this->officeType !== ''
            || $this->profession !== ''
            || $this->status !== 'in_service'
            || $this->isCustomSorted();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'governorate', 'office', 'officeType', 'profession');
        $this->status = 'in_service';
        $this->resetSort();
        $this->resetPage();
    }

    public function canEdit(): bool
    {
        return (bool) Auth::user()?->can('contractors.edit');
    }

    public function canDelete(): bool
    {
        return (bool) Auth::user()?->can('contractors.delete');
    }

    // ── النقل ───────────────────────────────────────────

    public function askTransfer(int $id): void
    {
        $this->guard('contractors.edit');

        $contractor = $this->scopedContractor($id);

        $this->transferContractorId   = $contractor->id;
        $this->transferContractorName = $contractor->name;
        // النقل داخل المحافظة هو الغالب، فتُفتح على محافظة المقر الحالي
        $this->transferGovernorate  = (string) ($contractor->currentAssignment?->office?->governorate_id ?? '');
        $this->transferOffice       = '';
        $this->transferDate         = WorkingDays::today()->toDateString();
        $this->resetValidation();
        $this->showTransfer         = true;
    }

    public function transfer(): void
    {
        $this->guard('contractors.edit');

        // ⚠️ لا يُنفَّذ إجراءٌ لم يُطلب تأكيده — النداء يصل في طلب مستقل
        if (! $this->showTransfer || ! $this->transferContractorId) {
            return;
        }

        $this->validate([
            'transferOffice' => ['required', 'integer'],
            'transferDate'   => ['required', 'date'],
        ]);

        // ⚠️ المقر يصل من العميل — فيُفحص على النطاق لا على وجوده فقط
        if (! ContractorScope::allowsOffice((int) $this->transferOffice)) {
            $this->addError('transferOffice', __('home.ct_worker_office_out_of_scope'));

            return;
        }

        $contractor = $this->scopedContractor($this->transferContractorId);
        $current  = $contractor->assignments()->whereNull('ended_on')->latest('started_on')->first();

        if ($current && $this->transferDate <= $current->started_on->toDateString()) {
            $this->addError('transferDate', __('home.ct_worker_transfer_date_invalid'));

            return;
        }

        DB::transaction(function () use ($contractor, $current) {
            // التسكين السابق ينتهي في اليوم السابق للنقل — فلا يوم بمقرّين
            $current?->update([
                'ended_on'   => \Carbon\CarbonImmutable::parse($this->transferDate)->subDay()->toDateString(),
                'end_reason' => ContractorAssignment::REASON_TRANSFER,
            ]);

            $contractor->assignments()->create([
                'office_id'  => (int) $this->transferOffice,
                'started_on' => $this->transferDate,
            ]);
        });

        $this->reset('showTransfer', 'transferContractorId', 'transferContractorName', 'transferGovernorate', 'transferOffice', 'transferDate');
        Flux::toast(variant: 'success', text: __('home.ct_worker_transferred'));
    }

    // ── إنهاء الخدمة ────────────────────────────────────

    public function askEnd(int $id): void
    {
        $this->guard('contractors.edit');

        $contractor = $this->scopedContractor($id);

        $this->endContractorId   = $contractor->id;
        $this->endContractorName = $contractor->name;
        $this->endDate         = WorkingDays::today()->toDateString();
        $this->resetValidation();
        $this->showEnd         = true;
    }

    public function endService(): void
    {
        $this->guard('contractors.edit');

        if (! $this->showEnd || ! $this->endContractorId) {
            return;
        }

        $this->validate(['endDate' => ['required', 'date']]);

        $contractor = $this->scopedContractor($this->endContractorId);
        $current  = $contractor->assignments()->whereNull('ended_on')->latest('started_on')->first();

        if (! $current) {
            $this->reset('showEnd', 'endContractorId', 'endContractorName', 'endDate');
            Flux::toast(variant: 'warning', text: __('home.ct_worker_already_ended'));

            return;
        }

        if ($this->endDate < $current->started_on->toDateString()) {
            $this->addError('endDate', __('home.ct_worker_end_date_invalid'));

            return;
        }

        $current->update([
            'ended_on'   => $this->endDate,
            'end_reason' => ContractorAssignment::REASON_LEFT,
        ]);

        $this->reset('showEnd', 'endContractorId', 'endContractorName', 'endDate');
        Flux::toast(variant: 'success', text: __('home.ct_worker_ended'));
    }

    // ── إعادة التسكين ───────────────────────────────────

    public function askReassign(int $id): void
    {
        $this->guard('contractors.edit');

        $contractor = $this->scopedContractor($id);

        $this->reassignContractorId   = $contractor->id;
        $this->reassignContractorName = $contractor->name;
        $this->reassignGovernorate  = (string) (
            $contractor->assignments()->orderByDesc('started_on')->first()?->office?->governorate_id ?? ''
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
        $this->guard('contractors.edit');

        // ⚠️ لا يُنفَّذ إجراءٌ لم يُطلب تأكيده — النداء يصل في طلب مستقل
        if (! $this->showReassign || ! $this->reassignContractorId) {
            return;
        }

        $this->validate([
            'reassignOffice' => ['required', 'integer'],
            'reassignDate'   => ['required', 'date'],
        ]);

        if (! ContractorScope::allowsOffice((int) $this->reassignOffice)) {
            $this->addError('reassignOffice', __('home.ct_worker_office_out_of_scope'));

            return;
        }

        $contractor = $this->scopedContractor($this->reassignContractorId);

        if ($contractor->isInService()) {
            $this->reset('showReassign', 'reassignContractorId', 'reassignContractorName', 'reassignGovernorate', 'reassignOffice', 'reassignDate');
            Flux::toast(variant: 'warning', text: __('home.ct_worker_already_in_service'));

            return;
        }

        $last = $contractor->assignments()->orderByDesc('ended_on')->first();

        // ⚠️ التاريخ يتجاوز نهاية آخر تسكين قطعاً — وإلا تداخلت المدد فعُدّ اليوم مرتين
        if ($last?->ended_on && $this->reassignDate <= $last->ended_on->toDateString()) {
            $this->addError('reassignDate', __('home.ct_worker_reassign_date_invalid'));

            return;
        }

        $contractor->assignments()->create([
            'office_id'  => (int) $this->reassignOffice,
            'started_on' => $this->reassignDate,
        ]);

        $this->reset('showReassign', 'reassignContractorId', 'reassignContractorName', 'reassignGovernorate', 'reassignOffice', 'reassignDate');
        Flux::toast(variant: 'success', text: __('home.ct_worker_reassigned'));
    }

    // ── الحذف ───────────────────────────────────────────

    public function askDelete(int $id): void
    {
        $this->guard('contractors.delete');

        $contractor = $this->scopedContractor($id);

        $this->deletingId      = $contractor->id;
        $this->deletingLabel   = $contractor->name;
        $this->deletingWarning = __('home.ct_worker_delete_warning');
        $this->showDelete      = true;
    }

    public function deleteRow(): void
    {
        $this->guard('contractors.delete');

        if (! $this->showDelete || ! $this->deletingId) {
            return;
        }

        $contractor = $this->scopedContractor($this->deletingId);

        // ⚠️ الحذف لتصحيح إدخالٍ خاطئ لا لطيّ تاريخ موظف: مَن له سجل حضور
        //    تُنهى خدمته ولا يُحذف — وإلا اختفى غيابه من تقارير شهرٍ مضى.
        if (AttendanceDay::forContractor($contractor)->exists()) {
            $this->reset('showDelete', 'deletingId', 'deletingLabel', 'deletingWarning');
            Flux::toast(variant: 'danger', text: __('home.ct_worker_has_attendance'));

            return;
        }

        $contractor->delete();

        $this->reset('showDelete', 'deletingId', 'deletingLabel', 'deletingWarning');
        Flux::toast(variant: 'success', text: __('home.ct_worker_deleted'));
    }

    /** ⚠️ كل معرّف يصل من العميل يُقرأ عبر النطاق — لا `findOrFail` مجرَّدة. */
    private function scopedContractor(int $id): Contractor
    {
        return ContractorScope::applyToContractors(Contractor::whereKey($id))->firstOrFail();
    }

    public function render()
    {
        $governorateId = ctype_digit($this->governorate) ? (int) $this->governorate : null;
        $officeId      = ctype_digit($this->office) ? (int) $this->office : null;
        $typeId        = ctype_digit($this->officeType) ? (int) $this->officeType : null;
        $professionId  = ctype_digit($this->profession) ? (int) $this->profession : null;

        // تاريخ بدء التسكين المفتوح كعمود محسوب — ليُرتَّب به بلا تحميل العلاقات كلها
        $currentStart = ContractorAssignment::query()
            ->select('started_on')
            ->whereColumn('contractor_id', 'contractors.id')
            ->whereNull('ended_on')
            ->orderByDesc('started_on')
            ->limit(1);

        $contractors = ContractorScope::applyToContractors(Contractor::query())
            ->select('contractors.*')
            ->selectSub($currentStart, 'current_started_on')
            ->with(['profession', 'currentAssignment.office.governorate'])
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
            // النوع كالمحافظة: المقر أخصّ منهما، فإن اختير أغنى عنهما
            ->when(
                $typeId && ! $officeId,
                fn ($q) => $q->whereHas('currentAssignment.office', fn ($o) => $o->where('type_id', $typeId))
            )
            ->when($professionId, fn ($q) => $q->where('profession_id', $professionId))
            ->when($this->status === 'in_service', fn ($q) => $q->inService())
            ->when($this->status === 'ended', fn ($q) => $q->whereDoesntHave('assignments', fn ($a) => $a->whereNull('ended_on')));

        // ⚠️ مُرجِّح ثابت: بلا ترتيبٍ حاسم يتبدّل موضع الصفوف المتساوية بين الصفحتين
        $contractors = $this->applySorting($contractors, 'contractors.id')
            ->paginate($this->perPage());

        return view('livewire.contractors.index', [
            'contractors'    => $contractors,
            // ⚠️ الفرق بين «لا مدخلين بعد» و«الفلتر لم يطابق» يُقرأ من النطاق لا من الفلاتر:
            //    فلتر الحالة الافتراضي (على رأس العمل) يُخفي المؤرشَفين وهو ليس فلتراً مفعّلاً.
            //    والاستعلام الإضافي لا يقع إلا على صفحة فارغة.
            'hasAnyContractor' => $contractors->total() > 0
                || ContractorScope::applyToContractors(Contractor::query())->exists(),
            'governorates' => ContractorScope::governorateOptions(),
            'offices'      => ContractorScope::officeOptions($governorateId, $typeId),
            'officeTypes'  => OfficeType::orderBy('name')->get(['id', 'name']),
            'professions'  => Profession::ordered()->get(['id', 'name']),
            // ⚠️ قوائم المودالين مستقلة عن فلتر الشاشة: النقل قد يكون إلى محافظة
            //    أخرى داخل النطاق، وفلترُ الشاشة سؤالٌ آخر (مَن أعرض الآن؟).
            'transferOffices' => ContractorScope::officeOptions(
                ctype_digit($this->transferGovernorate) ? (int) $this->transferGovernorate : null
            ),
            'reassignOffices' => ContractorScope::officeOptions(
                ctype_digit($this->reassignGovernorate) ? (int) $this->reassignGovernorate : null
            ),
        ]);
    }
}
