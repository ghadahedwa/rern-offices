<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAttachment;
use Illuminate\Support\Facades\Storage;

/**
 * يعرض صورة أمر الدفع — الملفات على القرص الخاص فلا رابط مباشر لها.
 *
 * ⚠️ الحارس `finance.view` هنا لا في الصفحة وحدها: الرابط يُنسخ ويُفتح منفرداً.
 */
class FinanceAttachmentController extends Controller
{
    public function __invoke(FinanceAttachment $attachment)
    {
        abort_unless(auth()->user()?->can('finance.view'), 403);

        $disk = Storage::disk(FinanceAttachment::DISK);
        abort_unless($disk->exists($attachment->path), 404);

        // العارض في الصفحة يقرأ الملف معروضاً، وزرّ «تنزيل» يطلبه بـ`?download=1`
        return request()->boolean('download')
            ? $disk->download($attachment->path, $attachment->downloadName())
            : $disk->response($attachment->path, $attachment->downloadName());
    }
}
