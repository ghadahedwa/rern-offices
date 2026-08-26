<?php

namespace App\Livewire\Warehouses\Items;

use App\Livewire\Concerns\WithPerPage;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\WarehouseIncomingItem;
use App\Models\WarehouseTransferItem;
use App\Support\ArabicDigits;
use App\Support\ArabicText;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('الأصناف')]
class Index extends Component
{
    use WithPagination;
    use WithPerPage;
    use WithTableSorting;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** معرّف قسم، أو 'none' للأصناف بلا قسم، أو '' للكل. */
    #[Url(as: 'category', except: '')]
    public string $categoryFilter = '';

    /** معرّف وحدة، أو 'none' للأصناف بلا وحدة، أو '' للكل. */
    #[Url(as: 'unit', except: '')]
    public string $unitFilter = '';

    /** 'yes' نشط · 'no' غير نشط · '' الكل. */
    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

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

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingUnitFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'categoryFilter', 'unitFilter', 'statusFilter');
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->categoryFilter !== ''
            || $this->unitFilter !== '' || $this->statusFilter !== '';
    }

    protected function sortableColumns(): array
    {
        return [
            'name'      => 'items.name',
            'code'      => 'items.code',
            'category'  => ['item_categories.order', 'item_categories.name'],
            'unit'      => 'item_units.name',
            'min_stock' => 'items.min_stock',
            'status'    => 'items.is_active',
        ];
    }

    /** ترتيب الدفتر — تعريفه الواحد في Item::statementOrder. */
    protected function defaultOrder(Builder $query): Builder
    {
        return Item::statementOrder($query);
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
            ->leftJoin('item_categories', 'items.item_category_id', '=', 'item_categories.id')
            // الوحدة مضمومة لأجل الترتيب بها؛ والعرض يبقى على العلاقة المحمَّلة
            ->leftJoin('item_units', 'items.item_unit_id', '=', 'item_units.id')
            ->select('items.*')
            ->with(['unit', 'category'])
            // الفلتر يصل من الرابط، فقيمة غير 'none' وغير رقمية تُهمَل ولا تُمرَّر لاستعلام
            ->when($this->categoryFilter === 'none', fn ($q) => $q->whereNull('items.item_category_id'))
            ->when(ctype_digit($this->categoryFilter), fn ($q) => $q->where('items.item_category_id', (int) $this->categoryFilter))
            ->when($this->unitFilter === 'none', fn ($q) => $q->whereNull('items.item_unit_id'))
            ->when(ctype_digit($this->unitFilter), fn ($q) => $q->where('items.item_unit_id', (int) $this->unitFilter))
            // قيمة غير 'yes'/'no' تُهمَل — وإلا فسّرها المقارِن «غير نشط» صامتاً
            ->when(
                in_array($this->statusFilter, ['yes', 'no'], true),
                fn ($q) => $q->where('items.is_active', $this->statusFilter === 'yes')
            )
            // البحث يشمل رقم الصنف: الموظف يعرف أصناف الدفتر العقاري بأرقامها.
            // كلمة البحث تُحوَّل لأرقام هندية لأن العمود مخزَّن بها.
            ->when($this->search, function ($q) {
                $term = ArabicText::normalize($this->search);

                $q->where(fn ($sub) => $sub
                    ->whereRaw(ArabicText::sqlNormalize('items.name').' LIKE ?', ['%'.$term.'%'])
                    ->orWhereRaw(
                        ArabicText::sqlNormalize('items.code').' LIKE ?',
                        ['%'.ArabicDigits::toArabic($term).'%']
                    ));
            })
            ->tap(fn ($q) => $this->applySorting($q, 'items.id'))
            ->paginate($this->perPage());

        return view('livewire.warehouses.items.index', [
            'items'      => $items,
            'categories' => ItemCategory::orderBy('order')->orderBy('name')->get(),
            'units'      => ItemUnit::orderBy('name')->get(),
            'canManage'  => Auth::user()?->can('warehouses.settings'),
        ]);
    }
}
