<?php

namespace App\Livewire\Warehouses\Units;

use App\Models\ItemUnit;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('وحدات الأصناف')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showDelete = false;
    public ?int $deletingId = null;
    public string $deletingLabel = '';
    public string $deletingWarning = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('warehouses.settings'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function askDelete(int $id): void
    {
        abort_unless(Auth::user()?->can('warehouses.settings'), 403);
        $unit = ItemUnit::withCount('items')->findOrFail($id);
        $this->deletingId    = $unit->id;
        $this->deletingLabel = $unit->name;
        $this->deletingWarning = $unit->items_count > 0
            ? __('home.item_unit_in_use_warning', ['count' => $unit->items_count])
            : '';
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->can('warehouses.settings'), 403);
        if ($this->deletingId) {
            $unit = ItemUnit::withCount('items')->findOrFail($this->deletingId);
            if ($unit->items_count > 0) {
                Flux::toast(variant: 'danger', text: __('home.item_unit_in_use_warning', ['count' => $unit->items_count]));
                return;
            }
            $unit->delete();
            $this->reset('deletingId', 'deletingLabel', 'deletingWarning', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.item_unit_deleted'));
        }
    }

    public function render()
    {
        return view('livewire.warehouses.units.index', [
            'units'     => ItemUnit::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(15),
            'canManage' => Auth::user()?->can('warehouses.settings'),
        ]);
    }
}
