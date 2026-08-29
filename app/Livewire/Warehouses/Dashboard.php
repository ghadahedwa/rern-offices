<?php

namespace App\Livewire\Warehouses;

use App\Models\Item;
use App\Models\Warehouse;
use App\Models\WarehouseMovement;
use App\Models\WarehouseStock;
use App\Support\WarehouseScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('لوحة تحكم المخازن')]
class Dashboard extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::user()?->can('warehouses.index'), 403);
    }

    /** الأصناف التي وصل رصيدها في المخازن الرئيسية إلى الحد الأدنى أو أقل. */
    protected function lowStockItems()
    {
        // إجمالي رصيد كل صنف في المخازن الرئيسية (level = 1)
        // ⚠️ المخازن الرئيسية **داخل نطاق المستخدم**: مَن لا يملك الرئيسي لا
        //    يعنيه تنبيه حدّه الأدنى، وعرضُه له يُنبّه على ما لا يملك تداركه.
        $mainWarehouseIds = WarehouseScope::apply(
            Warehouse::whereHas('type', fn ($q) => $q->where('level', 1))
        )->pluck('warehouses.id');

        if ($mainWarehouseIds->isEmpty()) {
            return collect();
        }

        $stockByItem = WarehouseStock::query()
            ->whereIn('warehouse_id', $mainWarehouseIds)
            ->select('item_id', DB::raw('SUM(quantity) as qty'))
            ->groupBy('item_id')
            ->pluck('qty', 'item_id');

        return Item::query()
            ->whereNotNull('min_stock')
            ->get()
            ->map(function (Item $item) use ($stockByItem) {
                $item->current_qty = (int) ($stockByItem[$item->id] ?? 0);
                return $item;
            })
            ->filter(fn (Item $item) => $item->current_qty <= $item->min_stock)
            ->sortBy('current_qty')
            ->values();
    }

    public function render()
    {
        return view('livewire.warehouses.dashboard', [
            // كل أرقام اللوحة على نطاق قارئها — لوحةٌ تعرض ما لا يراه في
            // شاشاته تُخرج رقمين مختلفين للشيء الواحد
            'warehousesCount'  => WarehouseScope::apply(Warehouse::query())->count(),
            'itemsCount'       => Item::count(),
            'movementsMonth'   => WarehouseScope::apply(
                                        WarehouseMovement::whereMonth('created_at', now()->month)
                                            ->whereYear('created_at', now()->year),
                                        'warehouse_movements.warehouse_id'
                                     )->count(),
            'lowStock'         => $this->lowStockItems(),
            'recentMovements'  => WarehouseScope::apply(
                                        WarehouseMovement::with(['warehouse', 'item', 'user'])->latest('created_at'),
                                        'warehouse_movements.warehouse_id'
                                     )->limit(8)->get(),
            // ⚠️ ثلاثة أزرار لثلاثة أفعال، فثلاث رايات لا واحدة: المفتش يملك
            //    الافتتاحي وحده، وأمين المخزن الرئيسي يملك الوارد والنقل دونه.
            //    والراية الواحدة كانت تُظهر الثلاثة لمن يملك واحداً فيقع على ٤٠٣.
            'canIncoming'      => Auth::user()?->can('warehouses.incoming'),
            'canTransfer'      => Auth::user()?->can('warehouses.transfer'),
            'canOpening'       => Auth::user()?->can('warehouses.opening'),
        ]);
    }
}
