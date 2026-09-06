<?php

namespace App\Livewire\Contractors;

use App\Models\Contractor;
use App\Models\Profession;
use App\Support\ContractorScope;
use App\Support\WorkingDays;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * إضافة عامل متعاقد أو تعديل بياناته.
 *
 * ⚠️ **التسكين يُنشأ مع الإضافة فقط**، وتغييره بعدها من زرّي «نقل» و«إنهاء خدمة»
 *    في القائمة — لا من هذا الفورم: تعديل تاريخ الالتحاق أو المقر مباشرةً يعيد
 *    كتابة التاريخ، فيُنسب غيابُ شهرٍ مضى إلى مقرٍّ لم يكن فيه.
 */
#[Layout('layouts.app')]
#[Title('عامل متعاقد')]
class Create extends Component
{
    public ?Contractor $contractor = null;

    public string $name = '';
    public string $profession = '';
    public string $phone = '';
    public string $notes = '';

    // التسكين الأول — عند الإضافة وحدها
    public string $governorate = '';
    public string $office = '';
    public string $started_on = '';

    public function mount(?Contractor $contractor = null): void
    {
        if ($contractor?->exists) {
            abort_unless(Auth::user()?->can('contractors.edit'), 403);
            abort_unless(ContractorScope::allowsContractor($contractor), 403);

            $this->contractor = $contractor;
            $this->name       = $contractor->name;
            $this->profession = (string) $contractor->profession_id;
            $this->phone    = (string) $contractor->phone;
            $this->notes    = (string) $contractor->notes;

            return;
        }

        abort_unless(Auth::user()?->can('contractors.create'), 403);

        $this->started_on = WorkingDays::today()->toDateString();
        // الصفة الافتراضية أولى الصفات ترتيباً (مدخل بيانات) — صفة أغلب المسجَّلين
        $this->profession = (string) (Profession::selectable()->ordered()->value('id') ?? '');
    }

    public function updatedGovernorate(): void
    {
        $this->office = '';
    }

    public function save(): void
    {
        $isEditing = (bool) $this->contractor?->exists;

        abort_unless(Auth::user()?->can($isEditing ? 'contractors.edit' : 'contractors.create'), 403);

        $rules = [
            'name'  => ['required', 'string', 'max:255'],
            // ⚠️ الصفة تصل من العميل فتُفحص على الجدول لا على وجود قيمة فقط
            'profession' => ['required', 'integer', 'exists:professions,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        if (! $isEditing) {
            $rules['office']     = ['required', 'integer'];
            $rules['started_on'] = ['required', 'date'];
        }

        $this->validate($rules);

        $data = [
            'name'          => $this->name,
            'profession_id' => (int) $this->profession,
            'phone'         => $this->phone ?: null,
            'notes' => $this->notes ?: null,
        ];

        if ($isEditing) {
            abort_unless(ContractorScope::allowsContractor($this->contractor), 403);

            $this->contractor->update($data);
            Flux::toast(variant: 'success', text: __('home.ct_worker_updated'));
            $this->redirect(route('contractors.index'), navigate: true);

            return;
        }

        // ⚠️ المقر يصل من العميل — يُفحص على النطاق، وإلا سُكِّن مدخلٌ في محافظةٍ ليست له
        if (! ContractorScope::allowsAssignment((int) $this->office)) {
            $this->addError('office', __('home.ct_worker_office_out_of_scope'));

            return;
        }

        DB::transaction(function () use ($data) {
            $contractor = Contractor::create($data);

            $contractor->assignments()->create([
                'office_id'  => (int) $this->office,
                'started_on' => $this->started_on,
            ]);
        });

        Flux::toast(variant: 'success', text: __('home.ct_worker_created'));
        $this->redirect(route('contractors.index'), navigate: true);
    }

    public function render()
    {
        $governorateId = ctype_digit($this->governorate) ? (int) $this->governorate : null;

        return view('livewire.contractors.create', [
            'governorates' => ContractorScope::governorateOptions(),
            // الشغّالة وحدها: الاستراحة وتحت الإنشاء والمعلَّق ليست مقارَّ عمل
            'offices'      => ContractorScope::assignableOffices($governorateId),
            // المعطَّلة تختفي من الإضافة وتبقى على أصحابها — ومع صفة المعروض لئلا تختفي عند التعديل
            'professions'  => Profession::query()
                ->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $this->contractor?->profession_id))
                ->ordered()->get(['id', 'name']),
        ]);
    }
}
