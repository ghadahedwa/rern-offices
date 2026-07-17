<?php

namespace App\Livewire\Warehouses\Manage;

use App\Models\Warehouse;
use App\Models\WarehouseIncoming;
use App\Models\WarehouseTransfer;
use App\Support\ArabicText;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('إدارة المخازن')]
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
        return WarehouseIncoming::where('warehouse_id', $id)->exists()
            || WarehouseTransfer::where('from_warehouse_id', $id)->orWhere('to_warehouse_id', $id)->exists();
    }

    public function askDelete(int $id): void
    {
        abort_unless(Auth::user()?->can('warehouses.settings'), 403);
        $warehouse = Warehouse::findOrFail($id);
        $this->deletingId    = $warehouse->id;
        $this->deletingLabel = $warehouse->name;
        $this->deletingWarning = $this->isInUse($id) ? __('home.warehouse_in_use_warning') : '';
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->can('warehouses.settings'), 403);
        if ($this->deletingId) {
            if ($this->isInUse($this->deletingId)) {
                Flux::toast(variant: 'danger', text: __('home.warehouse_in_use_warning'));
                return;
            }
            Warehouse::findOrFail($this->deletingId)->delete();
            $this->reset('deletingId', 'deletingLabel', 'deletingWarning', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.warehouse_deleted'));
        }
    }

    public function render()
    {
        $warehouses = Warehouse::query()
            ->with(['type', 'governorate'])
            ->when($this->search, fn ($q) => $q->whereRaw(
                ArabicText::sqlNormalize('name').' LIKE ?',
                ['%'.ArabicText::normalize($this->search).'%']
            ))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.warehouses.manage.index', [
            'warehouses' => $warehouses,
            'canManage'  => Auth::user()?->can('warehouses.settings'),
        ]);
    }
}
