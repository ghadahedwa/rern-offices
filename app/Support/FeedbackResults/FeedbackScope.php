<?php

namespace App\Support\FeedbackResults;

use App\Models\FeedbackRejectedAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * نطاق رؤية المستخدم لبيانات رأي المواطن.
 *
 * super-admin يرى الكل، ومَن له `feedback.view` يرى **بيانات محافظاته وحدها**
 * (نفس pivot المقرات `governorate_user` — لا نطاق ثانياً للموديول).
 *
 * وُضع في كلاس مستقل لا في المكوّن حتى تسري نفس الفلترة على الشاشة
 * وعلى الملف المصدَّر معاً — ملف تصدير يتجاوز نطاق الرؤية تسريب بيانات.
 * وهو كذلك الحارس الأخير للحذف الجماعي: `bulkQuery()` يمرّ من هنا،
 * فمعرّف يُدسّ من العميل لصفٍّ خارج المحافظة لا يُمسّ.
 */
final class FeedbackScope
{
    public static function apply(Builder $query, ?User $user = null): Builder
    {
        $governorateIds = FeedbackAccess::governorateIds($user);

        // null = بلا حدّ (super-admin). المصفوفة الفارغة تعني «لا شيء» وتُطبَّق.
        if ($governorateIds === null) {
            return $query;
        }

        $model = $query->getModel();

        // جدول المحاولات المرفوضة بلا عمود governorate_id — نمرّ عبر علاقة المقر.
        // ⚠️ وصفوفه بلا مقر (honeypot / rate_limit قبل اختيار مقر) لا تنتمي لمحافظة
        //    أصلاً، فلا تُنسب لمشرف بعينه — يراها super-admin وحده.
        if ($model instanceof FeedbackRejectedAttempt) {
            return $query->whereHas('office', fn ($o) => $o->whereIn('governorate_id', $governorateIds));
        }

        // qualifyColumn: الاستعلام قد يُضمَّن في subquery بجداول أخرى (أولويات المقترحات)
        return $query->whereIn($model->qualifyColumn('governorate_id'), $governorateIds);
    }
}
