<?php

namespace App\Livewire\Warehouses\Concerns;

use App\Models\WarehouseAttachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * تجميع مرفقات مستند المخازن قبل حفظها (يستعمله الإنشاء والإضافة اللاحقة).
 *
 * ⚠️ الاختيار يُضاف ولا يستبدل: `wire:model` على حقل ملفات متعددة يستبدل
 *    القائمة كلها مع كل اختيار، فمَن اختار ملفاً ثم رجع يختار ثانياً يفقد
 *    الأول بلا أثرٍ ظاهر. فالحقل يُربط بـ`pickedFiles` وما يصل يُنقل إلى
 *    `attachments` ثم يُفرَّغ.
 * المكوّن يستعمل `WithFileUploads` أيضاً.
 */
trait CollectsAttachments
{
    /** المرفقات المجمَّعة حتى الحفظ */
    public array $attachments = [];

    /** ما وصل من آخر اختيار — ينتقل فوراً إلى `attachments` */
    public array $pickedFiles = [];

    /** مسارات خُزِّنت في هذا الحفظ — تُحذف إن تراجع ما بعدها */
    protected array $storedAttachmentPaths = [];

    /** كم ملفاً يقبل المستند بعد. الإضافة اللاحقة تطرح الموجود. */
    protected function attachmentLimit(): int
    {
        return WarehouseAttachment::MAX_PER_DOCUMENT;
    }

    public function updatedPickedFiles(): void
    {
        $this->resetErrorBag('attachments');
        $rejected = [];
        $overflow = false;

        foreach ($this->pickedFiles as $file) {
            if (Validator::make(['f' => $file], ['f' => WarehouseAttachment::FILE_RULES])->fails()) {
                $rejected[] = $file->getClientOriginalName();
                continue;
            }
            if (count($this->attachments) >= $this->attachmentLimit()) {
                $overflow = true;
                continue;
            }
            $this->attachments[] = $file;
        }

        $this->pickedFiles = [];

        $messages = [];
        if ($rejected) {
            $messages[] = __('home.wh_attachment_rejected', ['names' => implode('، ', $rejected)]);
        }
        if ($overflow) {
            $messages[] = __('home.wh_attachments_max', ['max' => $this->attachmentLimit()]);
        }
        if ($messages) {
            $this->addError('attachments', implode(' — ', $messages));
        }
    }

    public function removeAttachment(int $index): void
    {
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    protected function attachmentRules(): array
    {
        return [
            'attachments'   => ['required', 'array', 'min:1', 'max:' . $this->attachmentLimit()],
            'attachments.*' => WarehouseAttachment::FILE_RULES,
        ];
    }

    /** يخزّن المجمَّع على المستند ويتذكّر مساراته. */
    protected function storeAttachments(Model $document): void
    {
        foreach ($this->attachments as $file) {
            $this->storedAttachmentPaths[] = $document->addAttachment($file)->path;
        }
    }

    /** حين تتراجع المعاملة بعد التخزين: الصفوف تراجعت والملفات لا. */
    protected function discardStoredAttachments(): void
    {
        Storage::disk('public')->delete($this->storedAttachmentPaths);
        $this->storedAttachmentPaths = [];
    }
}
