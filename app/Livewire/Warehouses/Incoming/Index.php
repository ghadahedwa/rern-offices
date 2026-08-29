<?php

namespace App\Livewire\Warehouses\Incoming;

use App\Exceptions\WarehouseException;
use App\Livewire\Concerns\WithDateRange;
use App\Livewire\Concerns\WithPerPage;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\WarehouseIncoming;
use App\Support\ArabicText;
use App\Support\WarehouseScope;
use App\Support\WarehouseLedger;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('الوارد')]
class Index extends Component
{
    use WithDateRange;
    use WithPagination;
    use WithPerPage;
    use WithTableSorting;

    #[Url(as: 'q', except: '')]
    public string $search = '';

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

    public function resetFilters(): void
    {
        $this->reset('search', 'dateFrom', 'dateTo');
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->hasDateFilter();
    }

    protected function sortableColumns(): array
    {
        return [
            'received_at' => 'warehouse_incomings.received_at',
            'warehouse'   => 'warehouses.name',
            'supplier'    => 'warehouse_incomings.supplier_name',
            'items_count' => 'items_count',
        ];
    }

    protected function defaultOrder(Builder $query): Builder
    {
        return $query
            ->orderByDesc('warehouse_incomings.received_at')
            ->orderByDesc('warehouse_incomings.id');
    }

    public function view(int $id): void
    {
        $this->viewing = WarehouseIncoming::with(['warehouse', 'creator', 'items.item.unit'])->findOrFail($id);

        // ⚠️ المعرّف يصل من العميل في طلبٍ مستقل عن render — فلا تكفي فلترة القائمة
        abort_unless(WarehouseScope::allows($this->viewing->warehouse_id), 403);
        $this->showView = true;
    }

    public function askDelete(int $id): void
    {
        abort_unless(Auth::user()?->can('warehouses.delete'), 403);
        $incoming = WarehouseIncoming::with('warehouse')->findOrFail($id);
        abort_unless(WarehouseScope::allows($incoming->warehouse_id), 403);
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
            $incoming = WarehouseIncoming::findOrFail($this->deletingId);
            // ⚠️ الحذف يُرجع الرصيد، فيُفحص النطاق هنا أيضاً لا في askDelete وحدها
            abort_unless(WarehouseScope::allows($incoming->warehouse_id), 403);
            WarehouseLedger::reverseIncoming($incoming);
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
            ->tap(fn ($q) => WarehouseScope::apply($q, 'warehouse_incomings.warehouse_id'))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->whereRaw(
                    ArabicText::sqlNormalize('warehouse_incomings.supplier_name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                )->orWhereRaw(
                    ArabicText::sqlNormalize('warehouses.name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                );
            }))
            // received_at يومٌ كتبه المستخدم (مصبوب 'date') — يُقارَن كما هو بلا تحويل توقيت
            ->tap(fn ($q) => $this->applyDayRange($q, 'warehouse_incomings.received_at'))
            ->with('warehouse')
            ->withCount('items')
            ->tap(fn ($q) => $this->applySorting($q, 'warehouse_incomings.id'))
            ->paginate($this->perPage());

        return view('livewire.warehouses.incoming.index', [
            'incomings'  => $incomings,
            'canCreate'  => Auth::user()?->can('warehouses.incoming'),
            'canDelete'  => Auth::user()?->can('warehouses.delete'),
            'canAttach'  => Auth::user()?->can('warehouses.attachments'),
        ]);
    }
}
