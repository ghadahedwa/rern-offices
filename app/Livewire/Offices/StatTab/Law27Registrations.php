<?php

namespace App\Livewire\Offices\StatTab;

use App\Models\Office;
use App\Models\OfficeStat;
use App\Models\StatType;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;

class Law27Registrations extends Component
{
    use WithPagination;

    public Office $office;
    public bool $canEdit = false;

    public array $filterYear  = [];
    public array $filterMonth = [];

    public bool $showForm   = false;
    public bool $showDelete = false;

    public ?int $editingId = null;
    public ?int $formTypeId = null;
    public string $formName      = '';
    public string $formPeriod    = '';
    public string $formValueType = 'count';
    public string $formYear  = '';
    public string $formMonth = '';
    public string $formValue = '';

    public ?int $deletingId = null;
    public string $deletingLabel = '';

    public function mount(Office $office): void
    {
        $this->office = $office;

        foreach (StatType::where('group_key', 'law27_registrations')->pluck('id') as $id) {
            $this->filterYear[$id]  = '';
            $this->filterMonth[$id] = '';
        }
    }

    public function updatedFilterYear($value, $key): void
    {
        $this->resetPage('p' . $key);
    }

    public function updatedFilterMonth($value, $key): void
    {
        $this->resetPage('p' . $key);
    }

    public function openAdd(int $typeId): void
    {
        $type = StatType::findOrFail($typeId);

        $this->editingId = null;
        $this->fillMeta($type);
        $this->formYear  = '';
        $this->formMonth = '';
        $this->formValue = '';
        $this->resetValidation();

        $this->showForm = true;
    }

    public function openEdit(int $statId): void
    {
        $stat = OfficeStat::where('office_id', $this->office->id)->findOrFail($statId);

        $this->editingId = $statId;
        $this->fillMeta($stat->statType);
        $this->formYear  = (string) $stat->year;
        $this->formMonth = (string) ($stat->month ?? '');
        $this->formValue = (string) (0 + $stat->value);
        $this->resetValidation();

        $this->showForm = true;
    }

    private function fillMeta(StatType $type): void
    {
        $this->formTypeId    = $type->id;
        $this->formName      = $type->name;
        $this->formPeriod    = $type->period;
        $this->formValueType = $type->value_type;
    }

    public function save(): void
    {
        $type = StatType::findOrFail($this->formTypeId);

        $rules = [
            'formYear'  => 'required|integer|min:2022',
            'formValue' => 'required|numeric|min:0',
        ];
        if ($type->period === 'monthly') {
            $rules['formMonth'] = 'required|integer|between:1,12';
        }

        $this->validate($rules, [], [
            'formYear'  => 'السنة',
            'formMonth' => 'الشهر',
            'formValue' => $type->value_type === 'amount' ? 'المبلغ' : 'العدد',
        ]);

        $dup = OfficeStat::where('office_id', $this->office->id)
            ->where('stat_type_id', $type->id)
            ->where('year', $this->formYear)
            ->when($type->period === 'monthly', fn($q) => $q->where('month', $this->formMonth))
            ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
            ->exists();

        if ($dup) {
            $this->addError('formYear', 'هذه السنة مسجلة بالفعل');
            return;
        }

        $data = [
            'office_id'    => $this->office->id,
            'stat_type_id' => $type->id,
            'year'         => $this->formYear,
            'month'        => null,
            'value'        => $this->formValue,
        ];

        if ($this->editingId) {
            OfficeStat::where('id', $this->editingId)->update($data);
            $msg = 'تم التعديل';
        } else {
            OfficeStat::create($data);
            $msg = 'تمت الإضافة';
        }

        $this->showForm = false;
        Flux::toast(variant: 'success', text: $msg);
    }

    public function askDelete(int $statId): void
    {
        $stat = OfficeStat::where('office_id', $this->office->id)->with('statType')->findOrFail($statId);

        $this->deletingId = $statId;
        $this->deletingLabel = $stat->statType->name . ' — ' . $stat->year;

        $this->showDelete = true;
    }

    public function deleteRow(): void
    {
        if ($this->deletingId) {
            OfficeStat::where('office_id', $this->office->id)->where('id', $this->deletingId)->delete();
            $this->deletingId = null;
            $this->deletingLabel = '';
            $this->showDelete = false;
            Flux::toast(variant: 'success', text: 'تم الحذف');
        }
    }

    public function render()
    {
        $statTypes = StatType::where('group_key', 'law27_registrations')
            ->orderBy('order')
            ->get();

        $existing = [];
        foreach ($statTypes as $type) {
            $existing[$type->id] = OfficeStat::where('office_id', $this->office->id)
                ->where('stat_type_id', $type->id)
                ->when($this->filterYear[$type->id] ?? '', fn($q, $y) => $q->where('year', $y))
                ->orderByDesc('year')
                ->paginate(8, ['*'], 'p' . $type->id);
        }

        $years = range((int) date('Y'), 2022);

        return view('livewire.offices.stat-tab.law27-registrations', [
            'statTypes' => $statTypes,
            'existing'  => $existing,
            'years'     => $years,
        ]);
    }
}
