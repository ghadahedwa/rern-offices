<?php

namespace App\Livewire\Finance\CourtBuildingItems;

use App\Models\CourtBuildingItem;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('عنصر أبنية المحاكم')]
class Create extends Component
{
    public ?CourtBuildingItem $item = null;

    public string $name = '';
    public int $order = 0;
    public bool $is_active = true;

    public function mount(?CourtBuildingItem $courtBuildingItem = null): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        if ($courtBuildingItem?->exists) {
            $this->item      = $courtBuildingItem;
            $this->name      = $courtBuildingItem->name;
            $this->order     = $courtBuildingItem->order;
            $this->is_active = $courtBuildingItem->is_active;
        } else {
            $this->order = (int) CourtBuildingItem::max('order') + 1;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        $this->validate([
            'name'  => [
                'required', 'string', 'max:255',
                Rule::unique('court_building_items', 'name')->ignore($this->item?->id),
            ],
            'order' => ['integer', 'min:0', 'max:999'],
        ]);

        // ⚠️ لا يُعطَّل آخر عنصر مفعَّل: يصير فورم الشهر بلا عنصرٍ يُكتب فيه
        if ($this->item?->is_active && ! $this->is_active
            && CourtBuildingItem::active()->count() === 1) {
            $this->addError('is_active', __('home.fin_item_last_active'));

            return;
        }

        $data = [
            'name'      => $this->name,
            'order'     => $this->order,
            'is_active' => $this->is_active,
        ];

        if ($this->item?->exists) {
            $this->item->update($data);
            Flux::toast(variant: 'success', text: __('home.fin_item_updated'));
        } else {
            CourtBuildingItem::create($data);
            Flux::toast(variant: 'success', text: __('home.fin_item_created'));
        }

        $this->redirect(route('court-building-items.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.finance.court-building-items.create');
    }
}
