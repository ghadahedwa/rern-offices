<?php

namespace App\Livewire\Warehouses;

use App\Livewire\Warehouses\Concerns\CollectsAttachments;
use App\Models\WarehouseAttachment;
use App\Support\WarehouseScope;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * مرفقات مستند مخازن محفوظ: عرضها، وإضافة ما وصل متأخراً حتى الحدّ.
 * يُضمَّن في مودال العرض (قوائم الوارد/النقل/الصرف وتابات بروفايل المخزن).
 *
 * ⚠️ لا حذف للمرفق بعد الحفظ — المرفق سند الحركة، وحذفه يترك حركةً في
 *    الرصيد بلا سند. الخطأ يُصحَّح بإضافة الصحيح.
 */
class DocumentAttachments extends Component
{
    use WithFileUploads;
    use CollectsAttachments;

    /** إذن الإضافة لكل نوع = إذن إنشاء المستند نفسه */
    private const ADD_PERMISSION = [
        'incoming' => 'warehouses.incoming',
        'transfer' => 'warehouses.transfer',
        'issue'    => 'warehouses.issue',
    ];

    #[Locked]
    public string $type = '';

    #[Locked]
    public int $documentId = 0;

    public function mount(string $type, int $documentId): void
    {
        abort_unless(isset(WarehouseAttachment::DOCUMENTS[$type]), 404);

        $this->type       = $type;
        $this->documentId = $documentId;

        abort_unless(Auth::user()?->can('warehouses.attachments'), 403);
        abort_unless($this->visible($this->document()), 403);
    }

    protected function document(): Model
    {
        return (WarehouseAttachment::DOCUMENTS[$this->type])::findOrFail($this->documentId);
    }

    /** نطاق الرؤية كمودال العرض الذي يضمّ المكوّن */
    protected function visible(Model $doc): bool
    {
        return $this->type === 'transfer'
            ? WarehouseScope::allows($doc->from_warehouse_id) || WarehouseScope::allows($doc->to_warehouse_id)
            : WarehouseScope::allows($doc->warehouse_id);
    }

    /**
     * الإضافة لمن ينشئ هذا النوع وعلى المخزن الذي أنشأه منه —
     * النقل من المصدر وحده (كحارس إنشائه): المستلم يطالع ولا يضيف.
     */
    protected function canAdd(Model $doc): bool
    {
        $warehouseId = $this->type === 'transfer' ? $doc->from_warehouse_id : $doc->warehouse_id;

        return (bool) Auth::user()?->can(self::ADD_PERMISSION[$this->type])
            && WarehouseScope::allows($warehouseId);
    }

    protected function attachmentLimit(): int
    {
        return max(0, WarehouseAttachment::MAX_PER_DOCUMENT - $this->document()->attachments()->count());
    }

    public function save(): void
    {
        $doc = $this->document();
        abort_unless(Auth::user()?->can('warehouses.attachments') && $this->canAdd($doc), 403);

        $this->validate($this->attachmentRules());

        try {
            // ⚠️ العدّ داخل المعاملة وبقفل: إضافتان متزامنتان على مستندٍ فيه أربعة
            //    كانتا ستمرّان معاً فيصير سبعة
            DB::transaction(function () use ($doc) {
                $doc->newQuery()->whereKey($doc->getKey())->lockForUpdate()->first();

                if (count($this->attachments) > $this->attachmentLimit()) {
                    throw new \LengthException;
                }

                $this->storeAttachments($doc);
            });
        } catch (\LengthException) {
            $this->addError('attachments', __('home.wh_attachments_max', ['max' => $this->attachmentLimit()]));

            return;
        } catch (\Throwable $e) {
            $this->discardStoredAttachments();
            throw $e;
        }

        $this->attachments = [];
        Flux::toast(variant: 'success', text: __('home.wh_attachments_added'));
    }

    public function render()
    {
        $doc = $this->document();

        return view('livewire.warehouses.document-attachments', [
            'saved'  => $doc->attachments()->with('uploader')->get(),
            'canAdd' => $this->canAdd($doc),
            'limit'  => $this->attachmentLimit(),
        ]);
    }
}
