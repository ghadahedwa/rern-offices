<?php

namespace App\Livewire\DataEntry\Operators;

use App\Models\DataEntryOperator;
use App\Support\DataEntryScope;
use App\Support\WorkingDays;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * إضافة مدخل بيانات أو تعديل بياناته.
 *
 * ⚠️ **التسكين يُنشأ مع الإضافة فقط**، وتغييره بعدها من زرّي «نقل» و«إنهاء خدمة»
 *    في القائمة — لا من هذا الفورم: تعديل تاريخ الالتحاق أو المقر مباشرةً يعيد
 *    كتابة التاريخ، فيُنسب غيابُ شهرٍ مضى إلى مقرٍّ لم يكن فيه.
 */
#[Layout('layouts.app')]
#[Title('مدخل بيانات')]
class Create extends Component
{
    public ?DataEntryOperator $operator = null;

    public string $name = '';
    public string $phone = '';
    public string $notes = '';

    // التسكين الأول — عند الإضافة وحدها
    public string $governorate = '';
    public string $office = '';
    public string $started_on = '';

    public function mount(?DataEntryOperator $operator = null): void
    {
        if ($operator?->exists) {
            abort_unless(Auth::user()?->can('data-entry.edit'), 403);
            abort_unless(DataEntryScope::allowsOperator($operator), 403);

            $this->operator = $operator;
            $this->name     = $operator->name;
            $this->phone    = (string) $operator->phone;
            $this->notes    = (string) $operator->notes;

            return;
        }

        abort_unless(Auth::user()?->can('data-entry.create'), 403);

        $this->started_on = WorkingDays::today()->toDateString();
    }

    public function updatedGovernorate(): void
    {
        $this->office = '';
    }

    public function save(): void
    {
        $isEditing = (bool) $this->operator?->exists;

        abort_unless(Auth::user()?->can($isEditing ? 'data-entry.edit' : 'data-entry.create'), 403);

        $rules = [
            'name'  => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        if (! $isEditing) {
            $rules['office']     = ['required', 'integer'];
            $rules['started_on'] = ['required', 'date'];
        }

        $this->validate($rules);

        $data = [
            'name'  => $this->name,
            'phone' => $this->phone ?: null,
            'notes' => $this->notes ?: null,
        ];

        if ($isEditing) {
            abort_unless(DataEntryScope::allowsOperator($this->operator), 403);

            $this->operator->update($data);
            Flux::toast(variant: 'success', text: __('home.de_operator_updated'));
            $this->redirect(route('data-entry.index'), navigate: true);

            return;
        }

        // ⚠️ المقر يصل من العميل — يُفحص على النطاق، وإلا سُكِّن مدخلٌ في محافظةٍ ليست له
        if (! DataEntryScope::allowsOffice((int) $this->office)) {
            $this->addError('office', __('home.de_operator_office_out_of_scope'));

            return;
        }

        DB::transaction(function () use ($data) {
            $operator = DataEntryOperator::create($data);

            $operator->assignments()->create([
                'office_id'  => (int) $this->office,
                'started_on' => $this->started_on,
            ]);
        });

        Flux::toast(variant: 'success', text: __('home.de_operator_created'));
        $this->redirect(route('data-entry.index'), navigate: true);
    }

    public function render()
    {
        $governorateId = ctype_digit($this->governorate) ? (int) $this->governorate : null;

        return view('livewire.data-entry.operators.create', [
            'governorates' => DataEntryScope::governorateOptions(),
            'offices'      => DataEntryScope::officeOptions($governorateId),
        ]);
    }
}
