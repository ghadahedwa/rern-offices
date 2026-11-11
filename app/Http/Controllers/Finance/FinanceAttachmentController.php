<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAttachment;
use App\Support\Finance\FinanceAccess;
use Illuminate\Support\Facades\Storage;

/**
 * يعرض صورة أمر الدفع — الملفات على القرص الخاص فلا رابط مباشر لها.
 *
 * ⚠️ الحارس هنا لا في الصفحة وحدها: الرابط يُنسخ ويُفتح منفرداً — وهو **عرض حساب الصورة نفسه**
 *    (صاحب الحسابات العامة لا يفتح صور أبنية المحاكم برقمها).
 */
class FinanceAttachmentController extends Controller
{
    public function __invoke(FinanceAttachment $attachment)
    {
        abort_unless(FinanceAccess::canView(auth()->user(), $attachment->paymentOrder->month->account), 403);

        $disk = Storage::disk(FinanceAttachment::DISK);
        abort_unless($disk->exists($attachment->path), 404);

        // العارض في الصفحة يقرأ الملف معروضاً، وزرّ «تنزيل» يطلبه بـ`?download=1`
        return request()->boolean('download')
            ? $disk->download($attachment->path, $attachment->downloadName())
            : $disk->response($attachment->path, $attachment->downloadName());
    }
}
