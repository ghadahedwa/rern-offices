<?php

namespace App\Livewire\Warehouses\Incoming;

use App\Exceptions\WarehouseException;
use App\Models\WarehouseIncoming;
use App\Support\ArabicText;
use App\Support\WarehouseLedger;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('الوارد')]
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
    public ?WarehouseIncoming $viewing = null;

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
        $this->viewing = WarehouseIncoming::with(['warehouse', 'creator', 'items.item.unit'])->findOrFail($id);
        $this->showView = true;
    }

    public function askDelete(int $id): void
    {
        abort_unless(Auth::user()?->can('warehouses.delete'), 403);
        $incoming = WarehouseIncoming::with('warehouse')->findOrFail($id);
        $this->deletingId    = $incoming->id;
        $this->deletingLabel = ($incoming->warehouse?->name ?? '—').' — '.$incoming->received_at->format('Y-m-d');
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
            WarehouseLedger::reverseIncoming(WarehouseIncoming::findOrFail($this->deletingId));
            $this->reset('deletingId', 'deletingLabel', 'deletingWarning', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.wh_incoming_deleted'));
        } catch (WarehouseException $e) {
            $this->showDelete = false;
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function render()
    {
        $incomings = WarehouseIncoming::query()
            ->join('warehouses', 'warehouse_incomings.warehouse_id', '=', 'warehouses.id')
            ->select('warehouse_incomings.*')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->whereRaw(
                    ArabicText::sqlNormalize('warehouse_incomings.supplier_name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                )->orWhereRaw(
                    ArabicText::sqlNormalize('warehouses.name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                );
            }))
            ->when($this->dateFrom, fn ($q) => $q->where('warehouse_incomings.received_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->where('warehouse_incomings.received_at', '<=', $this->dateTo))
            ->with(['warehouse', 'items'])
            ->orderByDesc('warehouse_incomings.received_at')
            ->orderByDesc('warehouse_incomings.id')
            ->paginate(15);

        return view('livewire.warehouses.incoming.index', [
            'incomings'  => $incomings,
            'canCreate'  => Auth::user()?->can('warehouses.create'),
            'canDelete'  => Auth::user()?->can('warehouses.delete'),
            'canAttach'  => Auth::user()?->can('warehouses.attachments'),
        ]);
    }
}
