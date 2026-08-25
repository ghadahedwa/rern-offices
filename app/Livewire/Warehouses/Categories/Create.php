<?php

namespace App\Livewire\Warehouses\Categories;

use App\Models\ItemCategory;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('قسم أصناف')]
class Create extends Component
{
    public ?ItemCategory $itemCategory = null;

    public string $name = '';
    public ?int $order = null;
    public bool $is_active = true;

    public function mount(?ItemCategory $itemCategory = null): void
    {
        abort_unless(auth()->user()?->can('warehouses.settings'), 403);

        if ($itemCategory?->exists) {
            $this->itemCategory = $itemCategory;
            $this->name         = $itemCategory->name;
            $this->order        = $itemCategory->order;
            $this->is_active    = (bool) $itemCategory->is_active;
        } else {
            $this->order = (int) ItemCategory::max('order') + 1;
        }
    }

    protected function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'order'     => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['order'] = $data['order'] ?? 0;

        if ($this->itemCategory?->exists) {
            $this->itemCategory->update($data);
            Flux::toast(variant: 'success', text: __('home.item_category_updated'));
        } else {
            ItemCategory::create($data);
            Flux::toast(variant: 'success', text: __('home.item_category_created'));
        }

        $this->redirect(route('item-categories.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.warehouses.categories.create');
    }
}
