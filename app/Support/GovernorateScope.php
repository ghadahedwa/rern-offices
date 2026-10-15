<?php

namespace App\Support;

use App\Models\User;

/**
 * هل يقع سجلٌّ (مقر · سيارة) داخل محافظات المستخدم؟ — حارس الصفحات التي تفتح سجلاً بمعرّفه في الرابط.
 *
 * ⚠️ الصلاحية وحدها لا تكفي: `offices.view` تفتح المقر لا مقرات الجمهورية كلها. صفحات العرض
 *    والإحصائيات والـPDF كانت تفحص الصلاحية وحدها، فيفتح المستخدم مقرّ محافظة أخرى بكتابة رقمه.
 * ⚠️ بلا محافظات = لا شيء (لا «بلا حدّ»). super-admin وحده فوق النطاق.
 */
class GovernorateScope
{
    public static function allows(?User $user, ?int $governorateId): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $governorateId !== null
            && $user->governorates()->whereKey($governorateId)->exists();
    }
}
