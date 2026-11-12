<?php

namespace App\Support\Finance;

use App\Models\FinanceAccount;
use App\Models\FinanceMonth;
use App\Models\FinanceUnit;
use App\Models\User;
use Illuminate\Support\Collection;

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
     * إدخال الحسابات الثلاثة (والرصيد الافتتاحي). أبنية المحاكم لا تُدخَل إلا بوحدةٍ (`canEnterUnit`)،
     * و«الإدخال» عليها هنا لملاحظات صفحة الإجمالي وحدها: عرضه مع إدخال الوحدات.
     */
    public static function canEnter(?User $user, FinanceAccount $account): bool
    {
        return $account->isItemized()
            ? (bool) ($user?->can(self::UNITS_CREATE) && $user->can(self::COURT_TOTAL_VIEW))
            : (bool) $user?->can(self::ACCOUNTS_CREATE);
    }

    // ── الوحدات الحسابية: الصلاحية **و** الربط في فورم المستخدم ──

    /** السوبر أدمن يرى الوحدات كلها بلا ربط — وغيره ما رُبط به وحده */
    protected static function isLinked(User $user, FinanceUnit|int $unit): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        $id = $unit instanceof FinanceUnit ? $unit->id : $unit;

        return $user->financeUnits()->whereKey($id)->exists();
    }

    public static function canViewUnit(?User $user, FinanceUnit|int $unit): bool
    {
        return (bool) $user?->can(self::UNITS_VIEW) && self::isLinked($user, $unit);
    }

    /** ⚠️ الوحدة المعطَّلة لا تُدخل شهوراً جديدة (وتبقى شهورها تُصحَّح) */
    public static function canEnterUnit(?User $user, FinanceUnit $unit): bool
    {
        return $unit->is_active
            && (bool) $user?->can(self::UNITS_CREATE)
            && self::isLinked($user, $unit);
    }

    /** الوحدات التي يفتحها المستخدم بترتيبها — للقائمة الجانبية وروابط صفحة الإجمالي */
    public static function viewableUnits(?User $user): Collection
    {
        if (! $user?->can(self::UNITS_VIEW)) {
            return collect();
        }

        return $user->hasRole('super-admin')
            ? FinanceUnit::ordered()->get()
            : $user->financeUnits()->ordered()->get();
    }

    /** صورة أمر دفع: عرض حسابها — وصورة شهر وحدةٍ لعارض الإجمالي أو لمن يفتح وحدتها */
    public static function canViewMonth(?User $user, FinanceMonth $month): bool
    {
        if ($month->finance_unit_id !== null) {
            return (bool) $user?->can(self::COURT_TOTAL_VIEW) || self::canViewUnit($user, $month->finance_unit_id);
        }

        return self::canView($user, $month->account);
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
