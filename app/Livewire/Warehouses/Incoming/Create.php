<?php

namespace App\Livewire\Warehouses\Incoming;

use App\Models\Item;
use App\Models\Warehouse;
use App\Models\WarehouseIncoming;
use App\Support\WarehouseLedger;
use App\Support\WarehouseScope;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('تسجيل وارد')]
class Create extends Component
{
    use \App\Livewire\Warehouses\Concerns\FiltersItemsByCategory;
    use WithFileUploads;

    public ?int $warehouse_id = null;
    public string $received_at = '';
    public ?string $supplier_name = null;

    /** صفوف الأصناف: كل عنصر ['item_id' => null, 'quantity' => null] */
    public array $lines = [];

    public $attachment = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('warehouses.incoming'), 403);
        // «اليوم» بالتوقيت المحلي — now() بـ UTC يعطي تاريخ الأمس بين ١٢ و٣ فجراً بتوقيت مصر
        $this->received_at = \App\Support\LocalTime::date(now());
        $this->lines = [$this->emptyLine()];
    }

    public function addLine(): void
    {
        $this->lines[] = $this->emptyLine();
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
        if (empty($this->lines)) {
            $this->lines = [$this->emptyLine()];
        }
    }

    protected function rules(): array
    {
        return [
            'warehouse_id'      => ['required', 'exists:warehouses,id'],
            'received_at'       => ['required', 'date'],
            'supplier_name'     => ['nullable', 'string', 'max:255'],
            'lines'             => ['array', 'min:1'],
            'lines.*.item_id'   => ['nullable', 'exists:items,id'],
            'lines.*.quantity'  => ['nullable', 'integer', 'min:1'],
            'attachment'        => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $warehouse = Warehouse::with('type')->findOrFail($this->warehouse_id);

        // ⚠️ المعرّف يصل من العميل — والمنسدلة المفلترة لا تحرس الطلب
        abort_unless(WarehouseScope::allows($warehouse->id), 403);

        if (! $warehouse->isMain()) {
            $this->addError('warehouse_id', __('home.wh_main_warehouse_only'));
            return;
        }

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

        $path = $this->attachment->store('warehouses/incoming', 'public');

        $incoming = WarehouseIncoming::create([
            'warehouse_id'             => $warehouse->id,
            'received_at'              => $this->received_at,
            'supplier_name'            => $this->supplier_name,
            'attachment_path'          => $path,
            'attachment_original_name' => $this->attachment->getClientOriginalName(),
            'created_by'               => Auth::id(),
        ]);

        foreach ($valid as $line) {
            $incoming->items()->create([
                'item_id'  => (int) $line['item_id'],
                'quantity' => (int) $line['quantity'],
            ]);
        }

        WarehouseLedger::recordIncoming($incoming->fresh('items'));

        Flux::toast(variant: 'success', text: __('home.wh_incoming_saved'));
        $this->redirect(route('warehouses.incoming.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.warehouses.incoming.create', [
            'warehouses' => WarehouseScope::apply(
                Warehouse::with('type')
                    ->whereHas('type', fn ($q) => $q->where('level', 1))
                    ->where('warehouses.is_active', true)
                    ->ordered()
            )->get(),
            // أصناف القسم المختار + ما اختاره المستخدم فعلاً (وإلا اختفى
            // الصنف من صفّه حين يضيق الفلتر) — التفصيل في الـtrait
            'lineItems'  => $this->lineItems(),
            'categories' => \App\Models\ItemCategory::orderBy('order')->orderBy('name')->get(),
        ]);
    }
}
