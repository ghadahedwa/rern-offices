<?php

namespace App\Livewire\Warehouses\Transfers;

use App\Exceptions\WarehouseException;
use App\Livewire\Concerns\WithDateRange;
use App\Livewire\Concerns\WithPerPage;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\WarehouseTransfer;
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
#[Title('النقل بين المخازن')]
class Index extends Component
{
    use WithDateRange;
    use WithPagination;
    use WithPerPage;
    use WithTableSorting;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /**
     * اتجاه النقل بالنسبة لمخازن المستخدم: 'in' وارد إليها · 'out' صادر منها.
     *
     * ⚠️ لا معنى له لمن نطاقه بلا حدّ (لا "مخازني" عنده) — فيُخفى من الشاشة
     *    وتُهمَل قيمته الآتية من الرابط. انظر activeDirection().
     */
    #[Url(as: 'direction', except: '')]
    public string $directionFilter = '';

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

    public function updatingDirectionFilter(): void
    {
        $this->resetPage();
    }

    /**
     * الاتجاه المفعَّل فعلاً — '' إن كانت القيمة تالفة أو النطاق بلا حدّ.
     *
     * ⚠️ القيمة تصل من الرابط، فقائمة بيضاء لا فحص وجود.
     */
    public function activeDirection(): string
    {
        if (WarehouseScope::unlimited()) {
            return '';
        }

        return in_array($this->directionFilter, ['in', 'out'], true) ? $this->directionFilter : '';
    }

    /** يُعرض المنتقي لمن له مخازن بعينها وحده. */
    public function showDirection(): bool
    {
        return ! WarehouseScope::unlimited();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'directionFilter', 'dateFrom', 'dateTo');
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->activeDirection() !== '' || $this->hasDateFilter();
    }

    protected function sortableColumns(): array
    {
        return [
            'transferred_at' => 'warehouse_transfers.transferred_at',
            'from'           => 'w_from.name',
            'to'             => 'w_to.name',
            'document_type'  => 'warehouse_transfers.document_type',
            'items_count'    => 'items_count',
        ];
    }

    protected function defaultOrder(Builder $query): Builder
    {
        return $query
            ->orderByDesc('warehouse_transfers.transferred_at')
            ->orderByDesc('warehouse_transfers.id');
    }

    public function view(int $id): void
    {
        $this->viewing = WarehouseTransfer::with(['fromWarehouse', 'toWarehouse', 'creator', 'items.item.unit'])->findOrFail($id);

        abort_unless(
            WarehouseScope::allows($this->viewing->from_warehouse_id)
            || WarehouseScope::allows($this->viewing->to_warehouse_id),
            403
        );
        $this->showView = true;
    }

    public function askDelete(int $id): void
    {
        abort_unless(Auth::user()?->can('warehouses.delete'), 403);
        $transfer = WarehouseTransfer::with(['fromWarehouse', 'toWarehouse'])->findOrFail($id);
        // ⚠️ الحذف يعكس الحركة على **الطرفين**، فيُشترط امتلاك المصدر لا أحدهما:
        //    وإلا حذف مستلِمٌ نقلاً أرسله إليه المركز فنقص رصيدُه وزاد الرئيسي بلا علمه.
        abort_unless(WarehouseScope::allows($transfer->from_warehouse_id), 403);
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
            $transfer = WarehouseTransfer::findOrFail($this->deletingId);
            abort_unless(WarehouseScope::allows($transfer->from_warehouse_id), 403);
            WarehouseLedger::reverseTransfer($transfer);
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
            // ⚠️ طرفان لا طرف: نقلٌ من الرئيسي إلى مخزنه يخصّه وإن لم يملك الرئيسي
            ->tap(fn ($q) => WarehouseScope::applyEither($q, 'warehouse_transfers.from_warehouse_id', 'warehouse_transfers.to_warehouse_id'))
            // الاتجاه يضيّق الطرفين إلى طرفٍ واحد — وهو نفسه نطاق المستخدم
            ->when($this->activeDirection() === 'in', fn ($q) => WarehouseScope::apply($q, 'warehouse_transfers.to_warehouse_id'))
            ->when($this->activeDirection() === 'out', fn ($q) => WarehouseScope::apply($q, 'warehouse_transfers.from_warehouse_id'))
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
            // transferred_at يومٌ كتبه المستخدم (مصبوب 'date') — بلا تحويل توقيت
            ->tap(fn ($q) => $this->applyDayRange($q, 'warehouse_transfers.transferred_at'))
            ->with(['fromWarehouse', 'toWarehouse'])
            ->withCount('items')
            ->tap(fn ($q) => $this->applySorting($q, 'warehouse_transfers.id'))
            ->paginate($this->perPage());

        return view('livewire.warehouses.transfers.index', [
            'transfers' => $transfers,
            'canCreate' => Auth::user()?->can('warehouses.transfer'),
            'canDelete' => Auth::user()?->can('warehouses.delete'),
            'canAttach' => Auth::user()?->can('warehouses.attachments'),
            'showDirection' => $this->showDirection(),
        ]);
    }
}
