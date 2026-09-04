<?php

namespace App\Livewire\Warehouses\Manage;

use App\Livewire\Concerns\WithPerPage;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\Warehouse;
use App\Models\WarehouseIncoming;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseType;
use App\Support\ArabicText;
use App\Support\WarehouseScope;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('إدارة المخازن')]
class Index extends Component
{
    use WithPagination;
    use WithPerPage;
    use WithTableSorting;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** معرّف نوع مخزن، أو '' للكل. */
    #[Url(as: 'type', except: '')]
    public string $typeFilter = '';

    public bool $showDelete = false;
    public ?int $deletingId = null;
    public string $deletingLabel = '';
    public string $deletingWarning = '';

    /**
     * مدخلان: مدير الإعدادات يدير المخازن، وصاحب المخزن يطالع قائمة مخازنه
     * ليبلغ بروفايل أحدها — وهو مدخله الوحيد إليه.
     *
     * ⚠️ رابطٌ على صفٍّ في جدول أرصدة لا يكفي مدخلاً: المخزن الفارغ بلا صفوف،
     *    و٢٩ مخزناً فارغة تنتظر أرصدتها الافتتاحية — أي أن المدخل يغيب عمّن
     *    يحتاجه اليوم بالضبط.
     */
    public function mount(): void
    {
        abort_unless(Auth::user()?->can('warehouses.index') || Auth::user()?->can('warehouses.settings'), 403);
    }

    /** هل يدير المستخدم المخازن (إنشاءً وتعديلاً وحذفاً)؟ */
    public function canManage(): bool
    {
        return (bool) Auth::user()?->can('warehouses.settings');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'typeFilter');
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->typeFilter !== '';
    }

    /**
     * الأعمدة المنضمّة (النوع/المحافظة) مسموح الترتيب بها لأن
     * withOrderingJoins() يضمّها للاستعلام على كل حال.
     */
    protected function sortableColumns(): array
    {
        return [
            'name'        => 'warehouses.name',
            // بالمستوى لا بالاسم: «رئيسي» قبل «إقليمي» قبل «فرعي» ترتيبٌ ذو معنى
            'type'        => ['warehouse_types.level', 'warehouse_types.order'],
            'governorate' => ['governorates.order', 'governorates.name'],
            'status'      => 'warehouses.is_active',
        ];
    }

    protected function defaultOrder(Builder $query): Builder
    {
        return $query->applyDefaultOrdering();
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
            ->withOrderingJoins()
            // ⚠️ شاشة إعدادات بلا نطاق لمديرها (يدير المخازن كلها ولو لم يُربط بواحد)،
            //    ومَن دخلها بصلاحية التشغيل وحدها يراها بنطاقه — قائمةُ مخازنه هو
            ->unless($this->canManage(), fn ($q) => WarehouseScope::apply($q))
            ->with(['type', 'governorate'])
            ->when($this->search, fn ($q) => $q->whereRaw(
                ArabicText::sqlNormalize('warehouses.name').' LIKE ?',
                ['%'.ArabicText::normalize($this->search).'%']
            ))
            // قيمة غير رقمية تصل من العميل تُهمَل ولا تُمرَّر لاستعلام
            ->when(ctype_digit($this->typeFilter), fn ($q) => $q->where('warehouses.warehouse_type_id', (int) $this->typeFilter))
            ->tap(fn ($q) => $this->applySorting($q, 'warehouses.id'))
            ->paginate($this->perPage());

        return view('livewire.warehouses.manage.index', [
            'warehouses' => $warehouses,
            // بترتيب المستوى نفسه المعتمد في عرض المخازن — لا أبجدياً
            'types'      => WarehouseType::orderBy('level')->orderBy('order')->get(),
            'canManage'  => $this->canManage(),
        ]);
    }
}
