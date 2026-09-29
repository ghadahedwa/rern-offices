<?php

namespace App\Models\Concerns;

use App\Models\WarehouseAttachment;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

/**
 * مرفقات مستند المخازن. الموديل يعرّف ثابتين:
 * `ATTACHMENT_TYPE` (مفتاحه في `WarehouseAttachment::DOCUMENTS`) و`ATTACHMENT_DIR`.
 */
trait HasWarehouseAttachments
{
    public static function bootHasWarehouseAttachments(): void
    {
        // ⚠️ صفّاً صفّاً لا حذفاً جماعياً — حذف الملف في حدث الموديل،
        //    والحذف الجماعي لا يُطلق الأحداث فتبقى الملفات يتيمة على القرص
        static::deleting(function (self $document) {
            $document->attachments()->get()->each->delete();
        });
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(WarehouseAttachment::class, 'document_id')
            ->where('document_type', static::ATTACHMENT_TYPE)
            ->orderBy('id');
    }

    /** يخزّن الملف على القرص ويسجّله على المستند. */
    public function addAttachment(UploadedFile $file): WarehouseAttachment
    {
        return WarehouseAttachment::create([
            'document_type' => static::ATTACHMENT_TYPE,
            'document_id'   => $this->getKey(),
            'path'          => $file->store(static::ATTACHMENT_DIR, 'public'),
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by'   => Auth::id(),
        ]);
    }
}
