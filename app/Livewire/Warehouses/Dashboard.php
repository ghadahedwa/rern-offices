<?php

namespace App\Livewire\Warehouses;

use App\Models\Item;
use App\Models\Warehouse;
use App\Models\WarehouseMovement;
use App\Models\WarehouseStock;
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
        $mainWarehouseIds = Warehouse::whereHas('type', fn ($q) => $q->where('level', 1))->pluck('id');

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
            'warehousesCount'  => Warehouse::count(),
            'itemsCount'       => Item::count(),
            'movementsMonth'   => WarehouseMovement::whereMonth('created_at', now()->month)
                                     ->whereYear('created_at', now()->year)->count(),
            'lowStock'         => $this->lowStockItems(),
            'recentMovements'  => WarehouseMovement::with(['warehouse', 'item', 'user'])
                                     ->latest('created_at')
                                     ->limit(8)
                                     ->get(),
            'canCreate'        => Auth::user()?->can('warehouses.create'),
        ]);
    }
}
