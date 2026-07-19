<?php

namespace App\Livewire\Warehouses\Manage;

use App\Models\Warehouse;
use App\Models\WarehouseIncoming;
use App\Models\WarehouseMovement;
use App\Models\WarehouseStock;
use App\Models\WarehouseTransfer;
use App\Support\ArabicText;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('بروفايل المخزن')]
class Show extends Component
{
    use WithPagination;

    public Warehouse $warehouse;
    public string $tab = 'stock';
    public bool $canEdit = false;

    // تاب الأرصدة
    public string $stockSearch = '';

    // تاب سجل الحركات
    public string $movItemFilter = '';
    public string $movTypeFilter = '';
    public string $movDateFrom = '';
    public string $movDateTo = '';

    // تاب الوارد (رئيسي فقط)
    public string $incSearch = '';
    public string $incDateFrom = '';
    public string $incDateTo = '';
    public bool $showViewIncoming = false;
    public ?WarehouseIncoming $viewingIncoming = null;

    // تاب النقل
    public string $transSearch = '';
    public string $transDateFrom = '';
    public string $transDateTo = '';
    public bool $showViewTransfer = false;
    public ?WarehouseTransfer $viewingTransfer = null;

    public function mount(Warehouse $warehouse): void
    {
        abort_unless(Auth::user()?->can('warehouses.settings'), 403);

        $this->warehouse = $warehouse->load('type', 'governorate', 'stocks.item');
        $this->canEdit   = (bool) Auth::user()?->can('warehouses.settings');

        if ($this->tab === 'incoming' && ! $this->warehouse->isMain()) {
            $this->tab = 'stock';
        }
    }

    public function setTab(string $tab): void
    {
        $validTabs = ['stock', 'movements', 'transfers'];
        if ($this->warehouse->isMain()) {
            $validTabs[] = 'incoming';
        }

        $this->tab = in_array($tab, $validTabs, true) ? $tab : 'stock';
    }

    public function updatingStockSearch(): void
    {
        $this->resetPage('stockPage');
    }

    public function updatingMovItemFilter(): void
    {
        $this->resetPage('movPage');
    }

    public function updatingMovTypeFilter(): void
    {
        $this->resetPage('movPage');
    }

    public function updatingMovDateFrom(): void
    {
        $this->resetPage('movPage');
    }

    public function updatingMovDateTo(): void
    {
        $this->resetPage('movPage');
    }

    public function updatingIncSearch(): void
    {
        $this->resetPage('incPage');
    }

    public function updatingIncDateFrom(): void
    {
        $this->resetPage('incPage');
    }

    public function updatingIncDateTo(): void
    {
        $this->resetPage('incPage');
    }

    public function updatingTransSearch(): void
    {
        $this->resetPage('transPage');
    }

    public function updatingTransDateFrom(): void
    {
        $this->resetPage('transPage');
    }

    public function updatingTransDateTo(): void
    {
        $this->resetPage('transPage');
    }

    public function viewIncoming(int $id): void
    {
        $this->viewingIncoming = WarehouseIncoming::with(['warehouse', 'items.item.unit'])->findOrFail($id);
        $this->showViewIncoming = true;
    }

    public function viewTransfer(int $id): void
    {
        $this->viewingTransfer = WarehouseTransfer::with(['fromWarehouse', 'toWarehouse', 'items.item.unit'])->findOrFail($id);
        $this->showViewTransfer = true;
    }

    protected function stockList()
    {
        return WarehouseStock::query()
            ->where('warehouse_id', $this->warehouse->id)
            ->join('items', 'warehouse_stocks.item_id', '=', 'items.id')
            ->select('warehouse_stocks.*')
            ->when($this->stockSearch, fn ($q) => $q->whereRaw(
                ArabicText::sqlNormalize('items.name').' LIKE ?',
                ['%'.ArabicText::normalize($this->stockSearch).'%']
            ))
            ->with('item.unit')
            ->orderBy('items.name')
            ->paginate(15, ['*'], 'stockPage');
    }

    protected function movementsList()
    {
        return WarehouseMovement::query()
            ->where('warehouse_id', $this->warehouse->id)
            ->when($this->movItemFilter, fn ($q) => $q->where('item_id', $this->movItemFilter))
            ->when($this->movTypeFilter, fn ($q) => $q->where('type', $this->movTypeFilter))
            ->when($this->movDateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->movDateFrom))
            ->when($this->movDateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->movDateTo))
            ->with(['item', 'user'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'movPage');
    }

    protected function incomingList()
    {
        if (! $this->warehouse->isMain()) {
            return null;
        }

        return WarehouseIncoming::query()
            ->where('warehouse_id', $this->warehouse->id)
            ->when($this->incSearch, fn ($q) => $q->whereRaw(
                ArabicText::sqlNormalize('supplier_name').' LIKE ?',
                ['%'.ArabicText::normalize($this->incSearch).'%']
            ))
            ->when($this->incDateFrom, fn ($q) => $q->where('received_at', '>=', $this->incDateFrom))
            ->when($this->incDateTo, fn ($q) => $q->where('received_at', '<=', $this->incDateTo))
            ->with('items')
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'incPage');
    }

    protected function transfersList()
    {
        return WarehouseTransfer::query()
            ->where(function ($q) {
                $q->where('from_warehouse_id', $this->warehouse->id)
                    ->orWhere('to_warehouse_id', $this->warehouse->id);
            })
            ->join('warehouses as w_from', 'warehouse_transfers.from_warehouse_id', '=', 'w_from.id')
            ->join('warehouses as w_to', 'warehouse_transfers.to_warehouse_id', '=', 'w_to.id')
            ->select('warehouse_transfers.*')
            ->when($this->transSearch, fn ($q) => $q->where(function ($q) {
                $q->whereRaw(
                    ArabicText::sqlNormalize('w_from.name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->transSearch).'%']
                )->orWhereRaw(
                    ArabicText::sqlNormalize('w_to.name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->transSearch).'%']
                )->orWhereRaw(
                    ArabicText::sqlNormalize('warehouse_transfers.document_type').' LIKE ?',
                    ['%'.ArabicText::normalize($this->transSearch).'%']
                );
            }))
            ->when($this->transDateFrom, fn ($q) => $q->where('warehouse_transfers.transferred_at', '>=', $this->transDateFrom))
            ->when($this->transDateTo, fn ($q) => $q->where('warehouse_transfers.transferred_at', '<=', $this->transDateTo))
            ->with(['fromWarehouse', 'toWarehouse', 'items'])
            ->orderByDesc('warehouse_transfers.transferred_at')
            ->orderByDesc('warehouse_transfers.id')
            ->paginate(15, ['*'], 'transPage');
    }

    public function render()
    {
        return view('livewire.warehouses.manage.show', [
            'stocks'    => $this->tab === 'stock' ? $this->stockList() : null,
            'movements' => $this->tab === 'movements' ? $this->movementsList() : null,
            'incomings' => $this->tab === 'incoming' ? $this->incomingList() : null,
            'transfers' => $this->tab === 'transfers' ? $this->transfersList() : null,
        ]);
    }
}
