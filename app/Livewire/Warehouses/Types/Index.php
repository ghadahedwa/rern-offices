<?php

namespace App\Livewire\Warehouses\Types;

use App\Models\WarehouseType;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('أنواع المخازن')]
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
        $type = WarehouseType::withCount('warehouses')->findOrFail($id);
        $this->deletingId    = $type->id;
        $this->deletingLabel = $type->name;
        $this->deletingWarning = $type->warehouses_count > 0
            ? __('home.wh_type_in_use_warning', ['count' => $type->warehouses_count])
            : '';
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->can('warehouses.settings'), 403);
        if ($this->deletingId) {
            $type = WarehouseType::withCount('warehouses')->findOrFail($this->deletingId);
            if ($type->warehouses_count > 0) {
                Flux::toast(variant: 'danger', text: __('home.wh_type_in_use_warning', ['count' => $type->warehouses_count]));
                return;
            }
            $type->delete();
            $this->reset('deletingId', 'deletingLabel', 'deletingWarning', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.warehouse_type_deleted'));
        }
    }

    public function render()
    {
        return view('livewire.warehouses.types.index', [
            'types'     => WarehouseType::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('level')->orderBy('order')
                ->paginate(15),
            'canManage' => Auth::user()?->can('warehouses.settings'),
        ]);
    }
}
