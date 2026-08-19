<?php

namespace App\Livewire\Correspondence\Entities;

use App\Models\CorrespondenceEntity;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('طرف المراسلات')]
class Create extends Component
{
    public ?CorrespondenceEntity $entity = null;

    public string $name = '';
    public string $code = '';
    public int $order = 0;
    public bool $is_active = true;

    public function mount(?CorrespondenceEntity $entity = null): void
    {
        abort_unless(auth()->user()?->can('correspondence.settings'), 403);

        if ($entity?->exists) {
            $this->entity    = $entity;
            $this->name      = $entity->name;
            $this->code      = $entity->code;
            $this->order     = $entity->order;
            $this->is_active = $entity->is_active;

            return;
        }

        // الطرف الجديد يأخذ آخر الطابور تلقائياً (نمط المحافظات) — وإلا قفز إلى أوّل القائمة
        $this->order = (int) CorrespondenceEntity::max('order') + 1;
    }

    public function save(): void
    {
        $id = $this->entity?->id;

        $this->validate([
            'name'  => ['required', 'string', 'max:255', Rule::unique('correspondence_entities', 'name')->ignore($id)],
            'code'  => ['required', 'string', 'max:8',   Rule::unique('correspondence_entities', 'code')->ignore($id)],
            'order' => ['required', 'integer', 'min:0'],
        ]);

        $data = [
            'name'      => $this->name,
            'code'      => $this->code,
            'order'     => $this->order,
            'is_active' => $this->is_active,
        ];

        if ($this->entity?->exists) {
            $this->entity->update($data);
            Flux::toast(variant: 'success', text: __('home.corr_entity_updated'));
        } else {
            CorrespondenceEntity::create($data);
            Flux::toast(variant: 'success', text: __('home.corr_entity_created'));
        }

        $this->redirect(route('correspondence-entities.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.correspondence.entities.create');
    }
}
