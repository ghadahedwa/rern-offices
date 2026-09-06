<?php

namespace App\Support;

use App\Models\Contractor;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * نطاق رؤية العاملين بالتعاقد — **نقطة التوسيع الوحيدة**.
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
class ContractorScope
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
     * يُقيّد استعلام العاملين بمحافظات المستخدم — عبر **مقر التسكين**،
     * فلا عمود محافظة على المدخل ولا على تسكينه (المقر مصدر الحقيقة).
     *
     * ⚠️ يكفي تسكينٌ واحد داخل النطاق: مَن نُقل من محافظتي يبقى مرئياً لي لأن
     *    تقارير الفترات التي خدم فيها عندي ما زالت تخصّني.
     */
    public static function applyToContractors(Builder $query, ?Authenticatable $user = null): Builder
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
    public static function allowsContractor(Contractor|int|null $contractor, ?Authenticatable $user = null): bool
    {
        $id = $contractor instanceof Contractor ? $contractor->getKey() : $contractor;

        if ($id === null) {
            return false;
        }

        return self::applyToContractors(Contractor::whereKey($id), $user)->exists();
    }

    /**
     * هل يصلح هذا المقر لتسكين عامل؟ — الحارس الفعلي قبل كل حفظ.
     *
     * ⚠️ شرطان لا شرط: **النوع تُسكَّن به عمالة متعاقدة** دائماً، **والنطاق** حين يكون الفعل مقيَّداً
     *    به (الإضافة). والنقل وإعادة التسكين مفتوحان على الجمهورية بقرار العميلة
     *    (2026-09-07): العامل يُنقل خارج محافظات المستخدم، ويبقى مرئياً له لأن
     *    له تسكيناً سابقاً في نطاقه.
     */
    public static function allowsAssignment(?int $officeId, bool $withinScope = true, ?Authenticatable $user = null): bool
    {
        if ($officeId === null) {
            return false;
        }

        $operational = Office::whereKey($officeId)->withContractWorkers()->exists();

        if (! $operational) {
            return false;
        }

        return $withinScope ? self::allowsOffice($officeId, $user) : true;
    }

    /**
     * مقرات منسدلات التسكين — **ما تُسكَّن به عمالة متعاقدة وحده**.
     *
     * ⚠️ منفصلة عن `officeOptions()` عمداً: تلك تخدم **فلتر الشاشة وقالب
     *    الاستيراد**، وهما يعرضان ما هو مسجَّل فعلاً لا ما يصلح للتسكين. وتضييق
     *    قالب الاستيراد كان يجعل مقراً مكتوباً في ملفٍ موزَّع سلفاً «غير معروف».
     */
    public static function assignableOffices(?int $governorateId = null, bool $withinScope = true, ?Authenticatable $user = null)
    {
        $ids = self::governorateIds($user);

        return Office::query()
            ->withContractWorkers()
            ->when($withinScope && $ids !== null, fn ($q) => $q->whereIn('governorate_id', $ids ?: [0]))
            ->when($governorateId, fn ($q) => $q->where('governorate_id', $governorateId))
            ->orderBy('name')
            ->get(['id', 'name', 'governorate_id'])
            ->each(fn (Office $office) => $office->setAttribute('short_name', ArabicText::shorten($office->name)));
    }

    /** محافظات منسدلات التسكين — كلها حين يكون الفعل غير مقيَّد بالنطاق. */
    public static function assignableGovernorates(bool $withinScope = true, ?Authenticatable $user = null)
    {
        if ($withinScope) {
            return self::governorateOptions($user);
        }

        return Governorate::query()->orderBy('order')->orderBy('name')->get(['id', 'name']);
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
