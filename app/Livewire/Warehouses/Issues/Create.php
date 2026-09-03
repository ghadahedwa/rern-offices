<?php

namespace App\Livewire\Warehouses\Issues;

use App\Exceptions\WarehouseException;
use App\Models\Item;
use App\Models\Office;
use App\Models\Warehouse;
use App\Models\WarehouseIssue;
use App\Models\WarehouseStock;
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

/**
 * تسجيل صرف من مخزن إلى **مقر** — النوع الخامس من الحركة.
 *
 * وصفه العميل بلفظه: «يودّي جهاز الكمبيوتر لفرع شبين القناطر… كده هيقدر
 * يخصم من الرصيد اللي عنده». وهو فعل **المفتش** لا أمين المخزن الرئيسي.
 */
#[Layout('layouts.app')]
#[Title('تسجيل صرف')]
class Create extends Component
{
    use WithFileUploads;

    public ?int $warehouse_id = null;
    public ?int $office_id = null;
    public string $issued_at = '';
    public ?string $document_type = null;

    /** صفوف الأصناف: كل عنصر ['item_id' => null, 'quantity' => null] */
    public array $lines = [];

    public $attachment = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('warehouses.issue'), 403);
        // «اليوم» بالتوقيت المحلي — now() بـUTC يعطي تاريخ الأمس بين ١٢ و٣ فجراً
        $this->issued_at = \App\Support\LocalTime::date(now());
        $this->lines     = [['item_id' => null, 'quantity' => null]];
    }

    /** تغيّر المخزن يبدّل قائمة المقرات — فالمقر المختار من مخزنٍ سابق يُلغى. */
    public function updatedWarehouseId(): void
    {
        $this->office_id = null;
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
            'warehouse_id'      => ['required', 'exists:warehouses,id'],
            'office_id'         => ['required', 'exists:offices,id'],
            'issued_at'         => ['required', 'date'],
            'document_type'     => ['nullable', 'string', 'max:255'],
            'lines'             => ['array', 'min:1'],
            'lines.*.item_id'   => ['nullable', 'exists:items,id'],
            'lines.*.quantity'  => ['nullable', 'integer', 'min:1'],
            // المرفق إجباري كالوارد والنقل — إذن الصرف هو سند الحركة
            'attachment'        => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }

    /**
     * مقار المخزن المختار.
     *
     * ⚠️ **مقار محافظة المخزن وحدها** — مخزن بنها يصرف لمقار القليوبية.
     *    والمخزن بلا محافظة («المخزن الرئيسي بالمصلحة») يخدم القطر كله،
     *    فتُعرض له المقرات جميعاً.
     */
    protected function officesQuery()
    {
        $warehouse = $this->warehouse_id
            ? Warehouse::find($this->warehouse_id)
            : null;

        if (! $warehouse) {
            return Office::query()->whereRaw('1 = 0');
        }

        return Office::query()
            ->when($warehouse->governorate_id, fn ($q) => $q->where('governorate_id', $warehouse->governorate_id))
            ->orderBy('name');
    }

    public function save(): void
    {
        $this->validate();

        // ⚠️ المعرّف يصل من العميل — والمنسدلة المفلترة لا تحرس الطلب
        abort_unless(WarehouseScope::allows((int) $this->warehouse_id), 403);

        // ⚠️ والمقر كذلك: قائمةُ المقرات مبنية على محافظة المخزن، فمعرّفٌ من
        //    محافظةٍ أخرى يُدسّ في الطلب يقيّد صرفاً لمقرٍّ لا يخدمه المخزن
        if (! $this->officesQuery()->whereKey($this->office_id)->exists()) {
            $this->addError('office_id', __('home.wh_issue_office_not_in_scope'));

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

        $path = $this->attachment->store('warehouses/issues', 'public');

        try {
            DB::transaction(function () use ($valid, $path) {
                $issue = WarehouseIssue::create([
                    'warehouse_id'             => $this->warehouse_id,
                    'office_id'                => $this->office_id,
                    'issued_at'                => $this->issued_at,
                    'document_type'            => $this->document_type,
                    'attachment_path'          => $path,
                    'attachment_original_name' => $this->attachment->getClientOriginalName(),
                    'created_by'               => Auth::id(),
                ]);

                foreach ($valid as $line) {
                    $issue->items()->create([
                        'item_id'  => (int) $line['item_id'],
                        'quantity' => (int) $line['quantity'],
                    ]);
                }

                WarehouseLedger::recordIssue($issue->fresh('items'));
            });
        } catch (WarehouseException $e) {
            // المرفق رُفع قبل المحاولة — يُحذف كي لا يبقى ملفٌّ بلا مستند
            Storage::disk('public')->delete($path);
            $this->addError('lines', $e->getMessage());

            return;
        }

        Flux::toast(variant: 'success', text: __('home.wh_issue_saved'));
        $this->redirect(route('warehouses.issues.index'), navigate: true);
    }

    public function render()
    {
        // أرصدة المخزن المختار — تُعرض بجوار كل صنف فلا يُصرف ما ليس فيه
        $stocks = $this->warehouse_id
            ? WarehouseStock::where('warehouse_id', $this->warehouse_id)->pluck('quantity', 'item_id')
            : collect();

        return view('livewire.warehouses.issues.create', [
            'warehouses' => WarehouseScope::apply(
                Warehouse::with('type')->where('warehouses.is_active', true)->ordered()
            )->get(),
            'offices' => $this->officesQuery()->get(),
            // ⚠️ `with(category)` لأجل التجميع في المنتقي — بلا تحميلها
            //    مسبقاً يقرأ القالب القسمَ لكل صنف على حدة (٣٧٧ استعلاماً)
            'items'   => Item::where('items.is_active', true)->with('category')->inStatementOrder()->get(),
            'stocks'  => $stocks,
        ]);
    }
}
