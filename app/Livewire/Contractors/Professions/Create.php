<?php

namespace App\Livewire\Contractors\Professions;

use App\Models\Profession;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('صفة عامل')]
class Create extends Component
{
    public ?Profession $profession = null;

    public string $name = '';
    public int $order = 0;
    public bool $is_active = true;

    public function mount(?Profession $profession = null): void
    {
        abort_unless(auth()->user()?->can('contractors.settings'), 403);

        if ($profession?->exists) {
            $this->profession = $profession;
            $this->name       = $profession->name;
            $this->order      = $profession->order;
            $this->is_active  = $profession->is_active;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('contractors.settings'), 403);

        $this->validate([
            'name'  => [
                'required', 'string', 'max:255',
                Rule::unique('professions', 'name')->ignore($this->profession?->id),
            ],
            'order' => ['integer', 'min:0', 'max:999'],
        ]);

        $data = [
            'name'      => $this->name,
            'order'     => $this->order,
            // ⚠️ الصفة الأساسية لا تُعطَّل: تعطيل «مدخل بيانات» يُخفيها من فورم
            //    الإضافة وهي صفة أغلب العاملين
            'is_active' => $this->profession?->is_system ? true : $this->is_active,
        ];

        if ($this->profession?->exists) {
            $this->profession->update($data);
            Flux::toast(variant: 'success', text: __('home.ct_profession_updated'));
        } else {
            Profession::create($data);
            Flux::toast(variant: 'success', text: __('home.ct_profession_created'));
        }

        $this->redirect(route('professions.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.contractors.professions.create');
    }
}
