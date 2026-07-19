<?php

namespace App\Livewire\Warehouses\Transfers;

use App\Exceptions\WarehouseException;
use App\Models\WarehouseTransfer;
use App\Support\ArabicText;
use App\Support\WarehouseLedger;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('النقل بين المخازن')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public bool $showDelete = false;
    public ?int $deletingId = null;
    public string $deletingLabel = '';
    public string $deletingWarning = '';

    public bool $showView = false;
    public ?WarehouseTransfer $viewing = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('warehouses.index'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function view(int $id): void
    {
        $this->viewing = WarehouseTransfer::with(['fromWarehouse', 'toWarehouse', 'creator', 'items.item.unit'])->findOrFail($id);
        $this->showView = true;
    }

    public function askDelete(int $id): void
    {
        abort_unless(Auth::user()?->can('warehouses.delete'), 403);
        $transfer = WarehouseTransfer::with(['fromWarehouse', 'toWarehouse'])->findOrFail($id);
        $this->deletingId    = $transfer->id;
        $this->deletingLabel = ($transfer->fromWarehouse?->name ?? '—').' ← '.($transfer->toWarehouse?->name ?? '—')
            .' — '.$transfer->transferred_at->format('Y-m-d');
        $this->deletingWarning = '';
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->can('warehouses.delete'), 403);

        if (! $this->deletingId) {
            return;
        }

        try {
            WarehouseLedger::reverseTransfer(WarehouseTransfer::findOrFail($this->deletingId));
            $this->reset('deletingId', 'deletingLabel', 'deletingWarning', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.wh_transfer_deleted'));
        } catch (WarehouseException $e) {
            $this->showDelete = false;
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function render()
    {
        $transfers = WarehouseTransfer::query()
            ->join('warehouses as w_from', 'warehouse_transfers.from_warehouse_id', '=', 'w_from.id')
            ->join('warehouses as w_to', 'warehouse_transfers.to_warehouse_id', '=', 'w_to.id')
            ->select('warehouse_transfers.*')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->whereRaw(
                    ArabicText::sqlNormalize('w_from.name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                )->orWhereRaw(
                    ArabicText::sqlNormalize('w_to.name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                )->orWhereRaw(
                    ArabicText::sqlNormalize('warehouse_transfers.document_type').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                );
            }))
            ->when($this->dateFrom, fn ($q) => $q->where('warehouse_transfers.transferred_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->where('warehouse_transfers.transferred_at', '<=', $this->dateTo))
            ->with(['fromWarehouse', 'toWarehouse', 'items'])
            ->orderByDesc('warehouse_transfers.transferred_at')
            ->orderByDesc('warehouse_transfers.id')
            ->paginate(15);

        return view('livewire.warehouses.transfers.index', [
            'transfers' => $transfers,
            'canCreate' => Auth::user()?->can('warehouses.create'),
            'canDelete' => Auth::user()?->can('warehouses.delete'),
            'canAttach' => Auth::user()?->can('warehouses.attachments'),
        ]);
    }
}
