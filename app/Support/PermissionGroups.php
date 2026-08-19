<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * تجميع الصلاحيات تحت عناوين — المصدر الواحد.
 *
 * تقرأ منه جهتان، ولو بنى كلٌّ منهما تجميعه لافترقا مع أوّل تعديل:
 *   - شبكة الصلاحيات في شاشة الأدوار  (permissions-grid.blade.php)
 *   - الأقسام المشروطة في فورم المستخدم (Users\Create · Users\Edit)
 *
 * ⚠️ المفتاح هو **العنوان** لا بادئة الصلاحية. والبادئة خاطئة في اتجاهين:
 *   - تفوّت: دور صلاحياته `vehicles.*` وحدها يحتاج المحافظات ولا يبدأ بـ`offices.`
 *   - تصطاد خطأً: `offices.settings` تبدأ بـ`offices.` وهي تحت «إدارة النظام» بلا نطاق محافظات
 *
 * ⚠️ كلاس لا config: التجميع يحتاج مطابقة بادئات واستثناءات، وملفات الـconfig
 *    لا تحمل closures (config:cache) — انظر التعليق في config/branches.php.
 */
class PermissionGroups
{
    /** عنوان الفرع => [اسم المجموعة الفرعية => مطابِق] · المطابِق: prefix (+except) أو names */
    public const GROUPS = [
        'home.branch_offices' => [
            'المحافظات'         => ['prefix' => 'governorates.'],
            'المقرات'           => ['prefix' => 'offices.', 'except' => ['offices.settings']],
            'السيارات المتنقلة' => ['prefix' => 'vehicles.'],
            'المطالبات'         => ['prefix' => 'claims.'],
        ],
        'home.branch_meetings' => [
            'الاجتماعات'        => ['prefix' => 'meetings.'],
        ],
        'home.branch_warehouses' => [
            'المخازن'           => ['prefix' => 'warehouses.', 'except' => ['warehouses.settings']],
        ],
        'home.branch_correspondence' => [
            'المراسلات'         => ['prefix' => 'correspondence.', 'except' => ['correspondence.settings']],
        ],
        'home.branch_system' => [
            'إعدادات المقرات'   => ['names' => ['offices.settings']],
            'إعدادات المخازن'   => ['names' => ['warehouses.settings']],
            // مدير النظام يدير قائمة الأطراف بلا أن ينتمي لطرف — فهذه لا تُظهر حقل الطرف
            'إعدادات المراسلات' => ['names' => ['correspondence.settings']],
        ],
    ];

    /** العنوان الذي يستلزم اختيار محافظات (نطاقه محافظة). */
    public const GOVERNORATE_BRANCH = 'home.branch_offices';

    /** العنوان الذي يستلزم اختيار طرف ومسمّى وظيفي (نطاقه جهة). */
    public const ENTITY_BRANCH = 'home.branch_correspondence';

    /**
     * الصلاحيات المعطاة مجمَّعة كما تعرضها شبكة الأدوار.
     *
     * @param  Collection<int, \Spatie\Permission\Models\Permission>  $permissions
     * @return array<string, array<string, Collection>>
     */
    public static function group(Collection $permissions): array
    {
        return collect(self::GROUPS)
            ->map(fn (array $subGroups) => collect($subGroups)
                ->map(fn (array $matcher) => $permissions->filter(
                    fn ($permission) => self::matches($permission->name, $matcher)
                ))
                ->all()
            )
            ->all();
    }

    /** هل ضمن هذه الصلاحيات ما يقع تحت العنوان المعطى؟ */
    public static function hasBranch(iterable $permissionNames, string $branchKey): bool
    {
        $matchers = self::GROUPS[$branchKey] ?? [];

        foreach ($permissionNames as $name) {
            foreach ($matchers as $matcher) {
                if (self::matches($name, $matcher)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** هل يستلزم هذا الدور اختيار محافظات؟ */
    public static function needsGovernorates(iterable $permissionNames): bool
    {
        return self::hasBranch($permissionNames, self::GOVERNORATE_BRANCH);
    }

    /** هل يستلزم هذا الدور اختيار طرف ومسمّى وظيفي؟ */
    public static function needsEntity(iterable $permissionNames): bool
    {
        return self::hasBranch($permissionNames, self::ENTITY_BRANCH);
    }

    protected static function matches(string $permissionName, array $matcher): bool
    {
        if (isset($matcher['names'])) {
            return in_array($permissionName, $matcher['names'], true);
        }

        if (in_array($permissionName, $matcher['except'] ?? [], true)) {
            return false;
        }

        return str_starts_with($permissionName, $matcher['prefix']);
    }
}
