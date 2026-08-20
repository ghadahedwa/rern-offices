<?php

namespace App\Support\FeedbackResults;

use App\Models\User;

/**
 * صلاحيات موديول نتائج رأي المواطن ونطاق محافظاته — المصدر الواحد.
 *
 * الحُرّاس متفرّقة بالضرورة (٤ شاشات + تصدير + كنترولرات PDF + الحذف الجماعي)،
 * لأن كل إجراء يصل في طلب مستقل عن `mount`. فلو كتب كل موضع فحصه بنفسه
 * لافترقت المواضع مع أول تعديل — وفرْق حارس واحد ثغرة لا خطأ تنسيق.
 *
 * ⚠️ الصلاحية والنطاق شيئان مختلفان: `feedback.view` تقول «يدخل الشاشة»،
 *    و`governorateIds()` تقول «يرى بيانات أي محافظات». مَن له الصلاحية بلا
 *    محافظات يفتح الشاشة ويجدها فارغة — وهو السلوك الصحيح لا عطل.
 */
final class FeedbackAccess
{
    public const VIEW = 'feedback.view';

    public const EXPORT = 'feedback.export';

    public const DELETE = 'feedback.delete';

    /**
     * شاشة المحاولات المرفوضة — صلاحية مستقلة عن `VIEW` بقرار المستخدمة (2026-08-20).
     * السبب: الشاشة **أمنية لا تقريرية** (سبب الرفض، الـIP، بصمة المتصفح)،
     * فليست كل مَن يقرأ آراء المواطنين يحتاجها.
     */
    public const REJECTED = 'feedback.rejected';

    /** كل صلاحيات الموديول — تقرأها الهجرة الزارعة وconfig/branches.php. */
    public const ALL = [self::VIEW, self::EXPORT, self::DELETE, self::REJECTED];

    public static function canView(?User $user): bool
    {
        return (bool) $user?->can(self::VIEW);
    }

    public static function canExport(?User $user): bool
    {
        return (bool) $user?->can(self::EXPORT);
    }

    public static function canDelete(?User $user): bool
    {
        return (bool) $user?->can(self::DELETE);
    }

    /** شاشة المحاولات المرفوضة وبطاقتها في اللوحة وسطرها في الملف المصدَّر. */
    public static function canViewRejected(?User $user): bool
    {
        return (bool) $user?->can(self::REJECTED);
    }

    /**
     * معرّفات المحافظات التي يرى المستخدم بياناتها.
     *
     * **null = بلا حدّ** (super-admin وحده) — وليست مصفوفة فارغة، لأن الفارغة
     * معناها «لا يرى شيئاً» وهو ما يقع فعلاً لمستخدم بصلاحية بلا محافظات.
     * الخلط بين المعنيين يفتح كل البيانات لمن لا محافظة له.
     *
     * @return array<int, int>|null
     */
    public static function governorateIds(?User $user): ?array
    {
        if ($user === null) {
            return [];
        }

        if ($user->hasRole('super-admin')) {
            return null;
        }

        return $user->governorates()->pluck('governorates.id')->all();
    }
}
