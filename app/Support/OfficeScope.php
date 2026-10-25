<?php

namespace App\Support;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * نطاق رؤية المقرات: محافظات المستخدم **و** قيد النوع/الوصف على أدواره.
 *
 * قيد النوع/الوصف (طلب العميل 2026-10-17 — دور «حياة كريمة») **على الدور لا على المستخدم**:
 * الدور يصف وظيفةً يشترك أصحابها في الأنواع نفسها، والمحافظات تبقى على المستخدم.
 *
 * القاعدة لكل دور: (أنواعه فارغة أو نوع المقر منها) **و** (أوصافه فارغة أو وصف المقر منها).
 * وبين الأدوار **الأوسع** (قرار المستخدمة): المقر ظاهر إن سمح به أيُّ دور، ودورٌ واحد بلا قيد
 * يرفع القيد كله — كالمستوى الذي هو أعلى أدواره.
 *
 * ⚠️ لا يدخل الحساب إلا دورٌ يفتح المقرات (`OFFICE_PERMISSIONS`): دور «السيارات» بلا قيد
 *    لأن القيد لا معنى له عليه، فلو احتُسب لرفع قيد «حياة كريمة» عمّن جمع الدورين.
 * ⚠️ أي شاشة أو تقرير أو تصدير جديد يقرأ المقرات يمرّ من هنا (`apply()` أو `applyRestrictions()`)
 *    — وإلا أخرج للدور المقيَّد مقرات كل الأنواع بلا أثرٍ ظاهر.
 */
class OfficeScope
{
    /** صلاحيات تفتح بيانات المقرات — دورٌ بلا واحدة منها لا يدخل حساب القيد */
    public const OFFICE_PERMISSIONS = [
        'offices.index',
        'offices.view',
        'offices.create',
        'offices.edit',
        'offices.delete',
        'offices.export',
        'offices.phone-directory',
        'offices.inspector-report.view',
        'offices.inspector-report.edit',
        'offices.counselor-report.view',
        'offices.counselor-report.edit',
    ];

    /**
     * قيود أدوار المستخدم، أو null إن لم يكن عليه قيد.
     *
     * @return null|list<array{types: list<int>, descriptions: list<int>}>
     */
    public static function restrictionsFor(?User $user): ?array
    {
        if (! $user || $user->hasRole('super-admin')) {
            return null;
        }

        $roleIds = DB::table('model_has_roles')
            ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('model_has_roles.model_type', $user->getMorphClass())
            ->where('model_has_roles.model_id', $user->getKey())
            ->whereIn('permissions.name', self::OFFICE_PERMISSIONS)
            ->distinct()
            ->pluck('model_has_roles.role_id');

        if ($roleIds->isEmpty()) {
            return null;
        }

        $types = DB::table('office_type_role')->whereIn('role_id', $roleIds)->get()->groupBy('role_id');
        $descs = DB::table('location_description_role')->whereIn('role_id', $roleIds)->get()->groupBy('role_id');

        $rules = [];

        foreach ($roleIds as $roleId) {
            $rule = [
                'types'        => $types->get($roleId)?->pluck('office_type_id')->map(fn ($id) => (int) $id)->all() ?? [],
                'descriptions' => $descs->get($roleId)?->pluck('location_description_id')->map(fn ($id) => (int) $id)->all() ?? [],
            ];

            // دورٌ يفتح المقرات بلا قيد يرفع القيد كله (الأوسع)
            if ($rule['types'] === [] && $rule['descriptions'] === []) {
                return null;
            }

            $rules[] = $rule;
        }

        return $rules;
    }

    /**
     * يقصر الاستعلام على مقرات المستخدم: المحافظات ثم قيد النوع/الوصف.
     * ⚠️ بلا محافظات = لا شيء (لا «بلا حدّ»). super-admin وحده فوق النطاق.
     */
    public static function apply(Builder|QueryBuilder $query, ?User $user, string $table = 'offices'): Builder|QueryBuilder
    {
        if ($user?->hasRole('super-admin')) {
            return $query;
        }

        $govIds = $user?->governorates()->pluck('governorates.id')->all() ?? [];
        $query->whereIn("{$table}.governorate_id", $govIds);

        return self::applyRestrictions($query, self::restrictionsFor($user), $table);
    }

    /** قيد النوع/الوصف وحده — لمن حسب القيود مرةً ويمرّرها لعدّة استعلامات */
    public static function applyRestrictions(Builder|QueryBuilder $query, ?array $rules, string $table = 'offices'): Builder|QueryBuilder
    {
        if ($rules === null) {
            return $query;
        }

        return $query->where(function ($q) use ($rules, $table) {
            foreach ($rules as $rule) {
                $q->orWhere(function ($w) use ($rule, $table) {
                    if ($rule['types'] !== []) {
                        $w->whereIn("{$table}.type_id", $rule['types']);
                    }
                    if ($rule['descriptions'] !== []) {
                        $w->whereIn("{$table}.location_description_id", $rule['descriptions']);
                    }
                });
            }
        });
    }

    /** هل يقع هذا المقر في نطاق المستخدم؟ — حارس الصفحات التي تفتح مقراً بمعرّفه في الرابط */
    public static function allows(?User $user, Office $office): bool
    {
        return GovernorateScope::allows($user, $office->governorate_id)
            && self::allowsAttributes($user, $office->type_id, $office->location_description_id);
    }

    /** هل يسمح قيد المستخدم بمقرٍّ بهذا النوع والوصف؟ — لفحص ما يُحفظ في فورم المقر */
    public static function allowsAttributes(?User $user, mixed $typeId, mixed $descriptionId): bool
    {
        $rules = self::restrictionsFor($user);

        if ($rules === null) {
            return true;
        }

        $typeId        = $typeId === null || $typeId === '' ? null : (int) $typeId;
        $descriptionId = $descriptionId === null || $descriptionId === '' ? null : (int) $descriptionId;

        foreach ($rules as $rule) {
            if (($rule['types'] === [] || in_array($typeId, $rule['types'], true))
                && ($rule['descriptions'] === [] || in_array($descriptionId, $rule['descriptions'], true))) {
                return true;
            }
        }

        return false;
    }

    /**
     * الأنواع التي قد يراها المستخدم — لتضييق المنسدلات. null = كلها.
     *
     * @return null|list<int>
     */
    public static function allowedTypeIds(?User $user): ?array
    {
        return self::allowedIds(self::restrictionsFor($user), 'types');
    }

    /** @return null|list<int> */
    public static function allowedDescriptionIds(?User $user): ?array
    {
        return self::allowedIds(self::restrictionsFor($user), 'descriptions');
    }

    private static function allowedIds(?array $rules, string $key): ?array
    {
        if ($rules === null) {
            return null;
        }

        $ids = [];

        foreach ($rules as $rule) {
            // دورٌ لا يقيّد هذا البُعد يفتحه كله
            if ($rule[$key] === []) {
                return null;
            }
            $ids = [...$ids, ...$rule[$key]];
        }

        return array_values(array_unique($ids));
    }

    /** أسماء الأدوار المقصورة على هذا النوع/الوصف (مفصولة بفاصلة)، أو null — لحارسَي الحذف */
    public static function rolesRestrictedTo(string $pivot, string $column, int $id): ?string
    {
        $names = DB::table($pivot)
            ->join('roles', 'roles.id', '=', "{$pivot}.role_id")
            ->where("{$pivot}.{$column}", $id)
            ->orderBy('roles.name')
            ->pluck('roles.name');

        return $names->isEmpty() ? null : $names->join('، ');
    }
}
