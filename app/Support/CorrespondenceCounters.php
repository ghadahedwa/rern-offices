<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * عدّادات المراسلات — المصدر الواحد للأرقام المعروضة.
 *
 * تقرأ منه ثلاثة مواضع: الظرف في الشريط العلوي · بنود المنيو · تبويبات الشاشات.
 * ⚠️ الظرف وبند الوارد يعرضان **نفس الرقم**؛ فبلا حفظٍ داخل الطلب يصير استعلامان
 *    لرقم واحد في الصفحة، وقد يختلفان لو تغيّر شيء بينهما.
 *
 * مُسجَّل singleton في AppServiceProvider، والحفظ في `$memo` يخصّ الطلب الواحد.
 * والاختبارات تستبدله بـ`$this->app->instance(...)` بلا أي واجهة اختبار في كود الإنتاج.
 *
 * ⚠️ **الأرقام صفر حتى تُنشأ جداول المكاتبات** (مرهونة بس٦ — مفتاح الترقيم).
 *    الأسلاك كلها موصولة، فلا يتغيّر مع الجداول إلا جسم كل دالة.
 *    وحين تُكتب: فهرس لكل عدّاد إلزامي — `COUNT` بلا فهرس يمشي مع كل صفحة.
 */
class CorrespondenceCounters
{
    /** @var array<string, int> */
    protected array $memo = [];

    /** مكاتبات في واردي لم تُقيَّد بعد — «لم يُقيَّد» لا «غير مقروء». */
    public function inbox(): int
    {
        return $this->remember('inbox', function () {
            // TODO(س٦): SELECT COUNT(*) FROM correspondence_recipients
            //           WHERE user_id = ? AND acknowledged_at IS NULL
            //           ← INDEX(user_id, acknowledged_at)
            return 0;
        });
    }

    /** مسودات تستلزم فعلاً منّي — لا كل مسوداتي، وإلا بقي العدّاد أحمر دائماً. */
    public function drafts(): int
    {
        return $this->remember('drafts', function () {
            // TODO(س٦): المعروضة عليّ للاعتماد + المرجَعة إليّ
            //           ← INDEX(status, from_entity_id)
            return 0;
        });
    }

    /** تكليفاتي المفتوحة. */
    public function assignments(): int
    {
        return $this->remember('assignments', function () {
            // TODO(س٦): WHERE assigned_to = ? AND completed_at IS NULL
            //           ← INDEX(assigned_to, completed_at)
            return 0;
        });
    }

    /** هل يُعرَض الظرف لهذا المستخدم؟ */
    public function envelopeVisible(): bool
    {
        // ⚠️ `correspondence.index` وحدها — لا `correspondence.settings`:
        //    مدير القوائم المرجعية لا صندوق وارد له.
        return (bool) Auth::user()?->can('correspondence.index');
    }

    protected function remember(string $key, callable $resolve): int
    {
        return $this->memo[$key] ??= (int) $resolve();
    }
}
