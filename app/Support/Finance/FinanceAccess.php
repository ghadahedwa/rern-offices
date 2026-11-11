<?php

namespace App\Support\Finance;

use App\Models\FinanceAccount;
use App\Models\User;

/**
 * صلاحيات الشئون المالية — المصدر الواحد (ملف العميل 2026-11-11 · الدفعة الأولى).
 *
 * صلاحياتٌ صغيرة يركّب منها العميل أدواره من شاشة الأدوار، بلا اسم دورٍ في الكود:
 *
 *   الحسابات العامة (الميكنة · اتصالات · وزارة المالية)   عرض · إدخال
 *   إجمالي أبنية المحاكم                                    عرض
 *   الوحدات الحسابية (المربوطة بالمستخدم)                   عرض · إدخال
 *   تصحيح · حذف · تقارير                                     على كل ما يراه
 *
 * ⚠️ كل حارسٍ يقرأ من هنا لا يكتب اسم صلاحيةٍ بنفسه — صفحة الحساب · الإدخال · الملاحظات ·
 *    الصور · القائمة الجانبية · الفرع.
 */
class FinanceAccess
{
    public const ACCOUNTS_VIEW    = 'finance.accounts.view';
    public const ACCOUNTS_CREATE  = 'finance.accounts.create';
    public const COURT_TOTAL_VIEW = 'finance.court-total.view';
    public const UNITS_VIEW       = 'finance.units.view';
    public const UNITS_CREATE     = 'finance.units.create';
    public const EDIT             = 'finance.edit';
    public const DELETE           = 'finance.delete';
    public const REPORTS          = 'finance.reports';

    /** بترتيب العرض في شاشة الأدوار */
    public const ALL = [
        self::ACCOUNTS_VIEW,
        self::ACCOUNTS_CREATE,
        self::COURT_TOTAL_VIEW,
        self::UNITS_VIEW,
        self::UNITS_CREATE,
        self::EDIT,
        self::DELETE,
        self::REPORTS,
    ];

    /** ما يفتح فرع الشئون المالية — صلاحيات العرض وحدها، والباقي لا يفتحه بلا عرض */
    public const BRANCH = [
        self::ACCOUNTS_VIEW,
        self::COURT_TOTAL_VIEW,
        self::UNITS_VIEW,
    ];

    /** صفحة الحساب: أبنية المحاكم بإجماليها، والثلاثة بالحسابات العامة */
    public static function canView(?User $user, FinanceAccount $account): bool
    {
        return (bool) $user?->can($account->isItemized() ? self::COURT_TOTAL_VIEW : self::ACCOUNTS_VIEW);
    }

    /**
     * إدخال شهرٍ جديد (والرصيد الافتتاحي وملاحظة الشهر المفتوح).
     *
     * ⚠️ أبنية المحاكم بـ`units.create` **مؤقتاً** حتى تصير شهورها على الوحدات (الدفعة الثانية)
     *    — فيُضاف هناك شرط الوحدة المربوطة.
     */
    public static function canEnter(?User $user, FinanceAccount $account): bool
    {
        return (bool) $user?->can($account->isItemized() ? self::UNITS_CREATE : self::ACCOUNTS_CREATE);
    }

    public static function canCorrect(?User $user): bool
    {
        return (bool) $user?->can(self::EDIT);
    }

    public static function canDelete(?User $user): bool
    {
        return (bool) $user?->can(self::DELETE);
    }
}
