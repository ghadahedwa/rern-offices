<?php

namespace App\Models;

use App\Support\Finance\FinanceCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * صورة أمر دفع — **صورة فقط** (قرار العميل 2026-11-07: لا PDF)، حتى خمس للأمر.
 *
 * ⚠️ **القرص الخاص `finance` لا `public`** (بخلاف مرفقات المخازن): مستندٌ مالي لا يُفتح برابطٍ
 *    مباشر بلا دخول — يُقرأ عبر `FinanceAttachmentController` بحارس عرض حسابها (`FinanceAccess::canView`).
 */
class FinanceAttachment extends Model
{
    public const DISK = 'finance';
    public const DIRECTORY = 'payment-orders';
    public const MAX_PER_ORDER = 5;

    /** قواعد الملف الواحد — صورة فقط، ١٠ ميجا (نفس سقف مرفقات المخازن) */
    public const FILE_RULES = ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'];

    protected $fillable = ['finance_payment_order_id', 'path', 'original_name', 'uploaded_by'];

    public function paymentOrder(): BelongsTo
    {
        return $this->belongsTo(FinancePaymentOrder::class, 'finance_payment_order_id');
    }

    public function url(): string
    {
        return route('finance.attachments.show', $this);
    }

    /**
     * اسم الملف عند تنزيله — من بياناته لا من اسمه الأصلي:
     * «حساب الميكنة (الوزير) - أكتوبر 2026 - أمر 1234 - 1.jpg».
     *
     * الأصلي غالباً اسم الكاميرا (IMG_20261102_143512.jpg) لا يقول شيئاً، ويبقى
     * محفوظاً في `original_name` للمراجعة. ⚠️ يُحسب لحظة التنزيل لا عند الحفظ:
     * رقم أمرٍ صُحِّح (المرحلة ج) يتبعه الاسم بلا تعديل.
     */
    public function downloadName(): string
    {
        $order = $this->paymentOrder;
        $month = $order->month;
        $index = $order->attachments->search(fn (self $a) => $a->is($this)) + 1;

        // ⚠️ «/» في رقم الأمر (123/2026) فاصلُ مسار — لا يصلح في اسم ملف
        $number = str_replace(['/', '\\'], '-', $order->number);

        return sprintf(
            '%s - %s - %s %s - %d.%s',
            $month->account->label(),
            FinanceCalendar::monthLabel(CarbonImmutable::parse($month->month)),
            __('home.fin_order_word'),
            $number,
            $index,
            strtolower(pathinfo($this->path, PATHINFO_EXTENSION))
        );
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
