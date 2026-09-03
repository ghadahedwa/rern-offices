<?php

namespace App\Livewire\Warehouses;

use App\Models\Item;
use App\Models\Warehouse;
use App\Support\WarehouseLedger;
use App\Support\WarehouseScope;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('الأرصدة الافتتاحية')]
class OpeningBalances extends Component
{
    public ?int $warehouse_id = null;

    /** صفوف الأصناف: كل عنصر ['item_id' => null, 'quantity' => null] */
    public array $lines = [];

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('warehouses.opening'), 403);
        $this->lines = [['item_id' => null, 'quantity' => null]];
    }

    public function addLine(): void
    {
        $this->lines[] = ['item_id' => null, 'quantity' => null];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
        if (empty($this->lines)) {
            $this->lines = [['item_id' => null, 'quantity' => null]];
        }
    }

    protected function rules(): array
    {
        return [
            'warehouse_id'       => ['required', 'exists:warehouses,id'],
            'lines'              => ['array', 'min:1'],
            'lines.*.item_id'    => ['nullable', 'exists:items,id'],
            'lines.*.quantity'   => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        // الصفوف الصالحة فقط: صنف مُختار وكمية مُدخَلة
        $valid = collect($this->lines)
            ->filter(fn ($l) => ! empty($l['item_id']) && $l['quantity'] !== null && $l['quantity'] !== '')
            ->values();

        if ($valid->isEmpty()) {
            $this->addError('lines', __('home.wh_add_at_least_one_line'));
            return;
        }

        if ($valid->pluck('item_id')->duplicates()->isNotEmpty()) {
            $this->addError('lines', __('home.wh_duplicate_item'));
            return;
        }

        $warehouse = Warehouse::findOrFail($this->warehouse_id);

        // ⚠️ المعرّف يصل من العميل، والافتتاحي **يكتب الرصيد كتابةً** — فبلا
        //    هذا الحارس يدسّ صاحبُ مخزنٍ معرّفَ الرئيسي فيمحو أرصدته.
        //    والمنسدلة المفلترة لا تكفي: الطلب يُبنى بغيرها.
        abort_unless(WarehouseScope::allows($warehouse->id), 403);

        $user = Auth::user();

        foreach ($valid as $line) {
            $item = Item::findOrFail($line['item_id']);
            WarehouseLedger::recordOpening($warehouse, $item, (int) $line['quantity'], $user);
        }

        Flux::toast(variant: 'success', text: __('home.wh_opening_saved'));
        $this->redirect(route('warehouses.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.warehouses.opening-balances', [
            // المنسدلة مفلترة كالحارس — خيارٌ خارج النطاق يقود إلى ٤٠٣
            'warehouses' => WarehouseScope::apply(
                Warehouse::with('type')->where('warehouses.is_active', true)->ordered()
            )->get(),
            // ⚠️ `with(category)` لأجل التجميع في المنتقي — بلا تحميلها
            //    مسبقاً يقرأ القالب القسمَ لكل صنف على حدة (٣٧٧ استعلاماً)
            'items'      => Item::where('items.is_active', true)->with('category')->inStatementOrder()->get(),
        ]);
    }
}
