<?php

namespace App\Support;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * تقريرا الزيارة على المقر (المفتش · المستشار) — المصدر الواحد لبنودهما وصلاحياتهما.
 *
 * التقريران **بنودٌ واحدة** على أعمدة `offices`: المفتش بلا بادئة (أعمدته الأصلية)،
 * والمستشار ببادئة `counselor_`. تقرأ منه صفحتا التقرير وقائمتاه والمنيو وزرّا شاشة
 * العرض والـPDF، فلا يفترق العرض عن الحفظ ولا تُفحص صلاحيةٌ في مكانٍ دون آخر.
 *
 * الصلاحيات **مستقلة عن `offices.view`/`offices.edit`** (قرار المستخدمة 2026-09-30):
 * مَن يكتب تقريره لا يلزمه تعديل بيانات المقر. والتعديل يشمل العرض.
 * والنطاق محافظة كالمقرات (`inScope`).
 */
class OfficeVisitReports
{
    public const INSPECTOR = 'inspector';
    public const COUNSELOR = 'counselor';

    /** نوع التقرير => [بادئة الأعمدة · عنوان الصفحة · عنوان القائمة في المنيو] — بترتيب العرض */
    public const REPORTS = [
        self::INSPECTOR => ['prefix' => '',           'label' => 'home.inspector_visit_report', 'list_label' => 'home.inspector_visit_reports'],
        self::COUNSELOR => ['prefix' => 'counselor_', 'label' => 'home.counselor_visit_report', 'list_label' => 'home.counselor_visit_reports'],
    ];

    /** البنود بأسمائها المجرّدة — العمود = البادئة + البند */
    public const FIELDS = [
        'visited_at',
        'structural_condition_id',
        'cleanliness_rating',
        'archive_rating',
        'work_schedule_commitment',
        'citizen_treatment_commitment',
        'surveillance_cameras',
        'office_needs',
        'negatives_and_solutions',
        'development_proposals',
    ];

    public const SURVEILLANCE_OPTIONS = ['available', 'not_available', 'broken'];

    public static function column(string $type, string $field): string
    {
        return self::REPORTS[$type]['prefix'] . $field;
    }

    /** @return array<string> أعمدة التقرير على `offices` */
    public static function columns(string $type): array
    {
        return array_map(fn ($field) => self::column($type, $field), self::FIELDS);
    }

    public static function viewPermission(string $type): string
    {
        return "offices.{$type}-report.view";
    }

    public static function editPermission(string $type): string
    {
        return "offices.{$type}-report.edit";
    }

    public static function canView(?User $user, string $type): bool
    {
        return (bool) $user && (
            $user->hasRole('super-admin')
            || $user->can(self::viewPermission($type))
            || $user->can(self::editPermission($type))
        );
    }

    public static function canEdit(?User $user, string $type): bool
    {
        return (bool) $user && (
            $user->hasRole('super-admin')
            || $user->can(self::editPermission($type))
        );
    }

    public static function isType(string $type): bool
    {
        return array_key_exists($type, self::REPORTS);
    }

    /**
     * يقصر استعلام المقرات على محافظات المستخدم (super-admin بلا قيد).
     * ⚠️ بلا محافظات = لا مقرات، لا كل المقرات.
     */
    public static function scopeOffices(Builder $query, ?User $user): Builder
    {
        if ($user?->hasRole('super-admin')) {
            return $query;
        }

        return $query->whereIn('offices.governorate_id', $user?->governorates()->pluck('governorates.id')->all() ?? []);
    }

    public static function inScope(?User $user, Office $office): bool
    {
        return self::scopeOffices(Office::query()->whereKey($office->getKey()), $user)->exists();
    }

    /** @return array<string> أنواع التقارير التي يراها المستخدم، بترتيب العرض */
    public static function viewable(?User $user): array
    {
        return array_values(array_filter(array_keys(self::REPORTS), fn ($type) => self::canView($user, $type)));
    }
}
