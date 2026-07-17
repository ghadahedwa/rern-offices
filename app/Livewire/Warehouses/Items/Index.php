<?php

namespace App\Livewire\Warehouses\Items;

use App\Models\Item;
use App\Models\WarehouseIncomingItem;
use App\Models\WarehouseTransferItem;
use App\Support\ArabicText;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('الأصناف')]
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

    protected function isInUse(int $id): bool
    {
        return WarehouseIncomingItem::where('item_id', $id)->exists()
            || WarehouseTransferItem::where('item_id', $id)->exists();
    }

    public function askDelete(int $id): void
    {
        abort_unless(Auth::user()?->can('warehouses.settings'), 403);
        $item = Item::findOrFail($id);
        $this->deletingId    = $item->id;
        $this->deletingLabel = $item->name;
        $this->deletingWarning = $this->isInUse($id) ? __('home.item_in_use_warning') : '';
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->can('warehouses.settings'), 403);
        if ($this->deletingId) {
            if ($this->isInUse($this->deletingId)) {
                Flux::toast(variant: 'danger', text: __('home.item_in_use_warning'));
                return;
            }
            Item::findOrFail($this->deletingId)->delete();
            $this->reset('deletingId', 'deletingLabel', 'deletingWarning', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.item_deleted'));
        }
    }

    public function render()
    {
        $items = Item::query()
            ->with('unit')
            ->when($this->search, fn ($q) => $q->whereRaw(
                ArabicText::sqlNormalize('name').' LIKE ?',
                ['%'.ArabicText::normalize($this->search).'%']
            ))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.warehouses.items.index', [
            'items'     => $items,
            'canManage' => Auth::user()?->can('warehouses.settings'),
        ]);
    }
}
