<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * صورة أمر دفع — صورة أو PDF، حتى خمس للأمر.
 *
 * ⚠️ **القرص الخاص `finance` لا `public`** (بخلاف مرفقات المخازن): مستندٌ مالي لا يُفتح برابطٍ
 *    مباشر بلا دخول — يُقرأ عبر `FinanceAttachmentController` بحارس `finance.view`.
 */
class FinanceAttachment extends Model
{
    public const DISK = 'finance';
    public const DIRECTORY = 'payment-orders';
    public const MAX_PER_ORDER = 5;

    /** قواعد الملف الواحد — نفس قواعد مرفقات المخازن */
    public const FILE_RULES = WarehouseAttachment::FILE_RULES;

    protected $fillable = ['finance_payment_order_id', 'path', 'original_name', 'uploaded_by'];

    public function paymentOrder(): BelongsTo
    {
        return $this->belongsTo(FinancePaymentOrder::class, 'finance_payment_order_id');
    }

    public function url(): string
    {
        return route('finance.attachments.show', $this);
    }

    public function isPdf(): bool
    {
        return str_ends_with(strtolower($this->path), '.pdf');
    }

    protected static function booted(): void
    {
        // الملف يُحذف بعد اعتماد المعاملة — لو تراجعت بقي الصفّ وقد ضاع ملفّه
        static::deleted(function (self $attachment) {
            DB::afterCommit(fn () => Storage::disk(self::DISK)->delete($attachment->path));
        });
    }
}
