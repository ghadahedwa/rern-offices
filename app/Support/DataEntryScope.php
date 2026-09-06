<?php

namespace App\Support;

use App\Models\DataEntryOperator;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * نطاق رؤية مدخلي البيانات — **نقطة التوسيع الوحيدة**.
 *
 * يمرّ منه كل استعلام في الفرع: الشاشة والمنسدلة والتقرير والتسجيل والتصدير.
 * وأي توسيع مستقبلي يُكتب هنا لا في شاشة، وإلا خرج الملف المصدَّر متجاوزاً الشاشة.
 *
 * ⚠️ **ثلاث حالات لا حالتان** (الدرس المدفوع ثمنه في `FeedbackAccess::governorateIds`):
 *   - `null`  = بلا حدّ (super-admin)
 *   - `[]`    = لا يرى شيئاً (صاحب صلاحية بلا محافظة مرتبطة)
 *   - `[...]` = محافظاته
 *   إرجاع `null` لمن لا محافظة له يفتح الجمهورية كلها.
 *
 * ⚠️ والمنسدلات تُفلتر كما تُفلتر النتائج — وإلا تسرّبت أسماء مقرات محافظاتٍ أخرى
 *    لمن يعدّل الرابط، وإن كانت صفوفها محجوبة.
 */
class DataEntryScope
{
    /** @return array<int, int>|null */
    public static function governorateIds(?Authenticatable $user = null): ?array
    {
        $user = $user ?? Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        if ($user->hasRole('super-admin')) {
            return null;
        }

        return $user->governorates()->pluck('governorates.id')->all();
    }

    public static function unlimited(?Authenticatable $user = null): bool
    {
        return self::governorateIds($user) === null;
    }

    /**
     * يُقيّد استعلام المدخلين بمحافظات المستخدم — عبر **مقر التسكين**،
     * فلا عمود محافظة على المدخل ولا على تسكينه (المقر مصدر الحقيقة).
     *
     * ⚠️ يكفي تسكينٌ واحد داخل النطاق: مَن نُقل من محافظتي يبقى مرئياً لي لأن
     *    تقارير الفترات التي خدم فيها عندي ما زالت تخصّني.
     */
    public static function applyToOperators(Builder $query, ?Authenticatable $user = null): Builder
    {
        $ids = self::governorateIds($user);

        if ($ids === null) {
            return $query;
        }

        if ($ids === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'assignments',
            fn (Builder $q) => $q->whereHas('office', fn (Builder $o) => $o->whereIn('governorate_id', $ids))
        );
    }

    /** هل يقع هذا المقر داخل نطاق المستخدم؟ — يُفحص قبل أي تسكين أو نقل. */
    public static function allowsOffice(?int $officeId, ?Authenticatable $user = null): bool
    {
        if ($officeId === null) {
            return false;
        }

        $ids = self::governorateIds($user);

        if ($ids === null) {
            return Office::whereKey($officeId)->exists();
        }

        return $ids !== [] && Office::whereKey($officeId)->whereIn('governorate_id', $ids)->exists();
    }

    /** هل يقع هذا المدخل داخل نطاق المستخدم؟ */
    public static function allowsOperator(DataEntryOperator|int|null $operator, ?Authenticatable $user = null): bool
    {
        $id = $operator instanceof DataEntryOperator ? $operator->getKey() : $operator;

        if ($id === null) {
            return false;
        }

        return self::applyToOperators(DataEntryOperator::whereKey($id), $user)->exists();
    }

    /** محافظات المنسدلة — مقصورة على النطاق. */
    public static function governorateOptions(?Authenticatable $user = null)
    {
        $ids = self::governorateIds($user);

        return Governorate::query()
            ->when($ids !== null, fn ($q) => $q->whereIn('id', $ids ?: [0]))
            ->orderBy('order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * مقرات المنسدلة — مقصورة على النطاق، وعلى محافظةٍ ونوعٍ بعينهما إن طُلبا.
     *
     * ⚠️ فلتر النوع يضيّق المنسدلة كما يضيّق الصفوف: خيارٌ بلا صفوف خلفه يُوهم
     *    المستخدم أن الشاشة معطّلة لا أن مقراتها من نوعٍ آخر.
     */
    public static function officeOptions(?int $governorateId = null, ?int $typeId = null, ?Authenticatable $user = null)
    {
        $ids = self::governorateIds($user);

        return Office::query()
            ->when($ids !== null, fn ($q) => $q->whereIn('governorate_id', $ids ?: [0]))
            ->when($governorateId, fn ($q) => $q->where('governorate_id', $governorateId))
            ->when($typeId, fn ($q) => $q->where('type_id', $typeId))
            ->orderBy('name')
            ->get(['id', 'name', 'governorate_id'])
            // ⚠️ اسم المقر يبلغ ١٣٦ حرفاً، والمنسدلة تتّسع لأطول خيار فيخرج جزؤها عن الشاشة.
            //    الخيار يُعرض مقصوصاً عند حدّ كلمة، والاسم الكامل يبقى في title.
            ->each(fn (Office $office) => $office->setAttribute('short_name', ArabicText::shorten($office->name)));
    }
}
