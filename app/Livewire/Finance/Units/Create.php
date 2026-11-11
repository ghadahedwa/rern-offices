<?php

namespace App\Livewire\Finance\Units;

use App\Models\FinanceUnit;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('وحدة حسابية')]
class Create extends Component
{
    public ?FinanceUnit $unit = null;

    public string $name = '';
    public int $order = 0;
    public bool $is_active = true;

    public function mount(?FinanceUnit $financeUnit = null): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        if ($financeUnit?->exists) {
            $this->unit      = $financeUnit;
            $this->name      = $financeUnit->name;
            $this->order     = $financeUnit->order;
            $this->is_active = $financeUnit->is_active;
        } else {
            $this->order = (int) FinanceUnit::max('order') + 1;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        $this->name = trim($this->name);
        $this->validate([
            'name'  => [
                'required', 'string', 'max:255',
                Rule::unique('finance_units', 'name')->ignore($this->unit?->id),
            ],
            'order' => ['integer', 'min:0', 'max:999'],
        ]);

        // ⚠️ لا تُعطَّل آخر وحدة مفعَّلة: يصير أبنية المحاكم بلا وحدةٍ تُدخل فيه
        if ($this->unit?->is_active && ! $this->is_active
            && FinanceUnit::active()->count() === 1) {
            $this->addError('is_active', __('home.fin_unit_last_active'));

            return;
        }

        $data = [
            'name'      => $this->name,
            'order'     => $this->order,
            'is_active' => $this->is_active,
        ];

        if ($this->unit?->exists) {
            $this->unit->update($data);
            Flux::toast(variant: 'success', text: __('home.fin_unit_updated'));
        } else {
            FinanceUnit::create($data);
            Flux::toast(variant: 'success', text: __('home.fin_unit_created'));
        }

        $this->redirect(route('finance-units.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.finance.units.create');
    }
}
