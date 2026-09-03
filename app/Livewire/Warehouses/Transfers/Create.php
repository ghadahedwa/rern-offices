<?php

namespace App\Livewire\Warehouses\Transfers;

use App\Exceptions\WarehouseException;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WarehouseTransfer;
use App\Support\WarehouseLedger;
use App\Support\WarehouseScope;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('تسجيل نقل')]
class Create extends Component
{
    use WithFileUploads;

    public ?int $from_warehouse_id = null;
    public ?int $to_warehouse_id = null;
    public string $transferred_at = '';
    public ?string $document_type = null;

    /** صفوف الأصناف: كل عنصر ['item_id' => null, 'quantity' => null] */
    public array $lines = [];

    public $attachment = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('warehouses.transfer'), 403);
        // «اليوم» بالتوقيت المحلي — انظر التعليق في Incoming\Create
        $this->transferred_at = \App\Support\LocalTime::date(now());
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
            'from_warehouse_id' => ['required', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id'   => ['required', 'exists:warehouses,id'],
            'transferred_at'    => ['required', 'date'],
            'document_type'     => ['nullable', 'string', 'max:255'],
            'lines'             => ['array', 'min:1'],
            'lines.*.item_id'   => ['nullable', 'exists:items,id'],
            'lines.*.quantity'  => ['nullable', 'integer', 'min:1'],
            'attachment'        => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
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

        // ⚠️ **المصدر وحده** يُحرَس بالنطاق لا الوجهة: أمين المخزن الرئيسي
        //    ينقل إلى مخازن المحافظات كلها، وحصرُ الوجهة في نطاقه يمنعه من
        //    عملِه نفسه. والخصم يقع على المصدر، فهو موضع الحراسة.
        abort_unless(WarehouseScope::allows((int) $this->from_warehouse_id), 403);

        $path = $this->attachment->store('warehouses/transfers', 'public');

        try {
            DB::transaction(function () use ($valid, $path) {
                $transfer = WarehouseTransfer::create([
                    'from_warehouse_id'        => $this->from_warehouse_id,
                    'to_warehouse_id'          => $this->to_warehouse_id,
                    'transferred_at'           => $this->transferred_at,
                    'document_type'            => $this->document_type,
                    'attachment_path'          => $path,
                    'attachment_original_name' => $this->attachment->getClientOriginalName(),
                    'created_by'               => Auth::id(),
                ]);

                foreach ($valid as $line) {
                    $transfer->items()->create([
                        'item_id'  => (int) $line['item_id'],
                        'quantity' => (int) $line['quantity'],
                    ]);
                }

                WarehouseLedger::recordTransfer($transfer->fresh('items'));
            });
        } catch (WarehouseException $e) {
            Storage::disk('public')->delete($path);
            $this->addError('lines', $e->getMessage());
            return;
        }

        Flux::toast(variant: 'success', text: __('home.wh_transfer_saved'));
        $this->redirect(route('warehouses.transfers.index'), navigate: true);
    }

    public function render()
    {
        $stocks = collect();
        if ($this->from_warehouse_id) {
            $stocks = WarehouseStock::where('warehouse_id', $this->from_warehouse_id)
                ->pluck('quantity', 'item_id');
        }

        return view('livewire.warehouses.transfers.create', [
            // الوجهة كل المخازن؛ والمصدر نطاقُ المستخدم ($sourceWarehouses في القالب)
            'warehouses'       => Warehouse::where('warehouses.is_active', true)->ordered()->get(),
            'sourceWarehouses' => WarehouseScope::apply(
                Warehouse::where('warehouses.is_active', true)->ordered()
            )->get(),
            // ⚠️ `with(category)` لأجل التجميع في المنتقي — بلا تحميلها
            //    مسبقاً يقرأ القالب القسمَ لكل صنف على حدة (٣٧٧ استعلاماً)
            'items'      => Item::where('items.is_active', true)->with('category')->inStatementOrder()->get(),
            'stocks'     => $stocks,
        ]);
    }
}
