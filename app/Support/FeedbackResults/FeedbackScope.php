<?php

namespace App\Support\FeedbackResults;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * نطاق رؤية المستخدم لبيانات رأي المواطن.
 *
 * مفتوح حالياً لأن الموديول كله super-admin فقط. **هذه هي النقطة الوحيدة**
 * التي تُضاف فيها فلترة محافظات المستخدم عند فتح الموديول لأدوار أخرى
 * (مع صلاحية feedback.view وحذف super_admin_only من config/branches.php).
 *
 * وُضع في كلاس مستقل لا في المكوّن حتى تسري نفس الفلترة على الشاشة
 * وعلى الملف المصدَّر معاً — ملف تصدير يتجاوز نطاق الرؤية تسريب بيانات.
 */
final class FeedbackScope
{
    public static function apply(Builder $query, ?User $user = null): Builder
    {
        return $query;
    }
}
