<?php

namespace App\Support;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * نطاق رؤية المخازن — **نقطة التوسيع الوحيدة**.
 *
 * يمرّ منه كل استعلام في الفرع: شاشةً ومنسدلةً وتقريراً وحذفاً. وأي توسيع
 * مستقبلي يُكتب هنا لا في شاشة، وإلا صار الملف المطبوع يتجاوز نطاق الشاشة.
 *
 * ⚠️ **ثلاث حالات لا حالتان**، والخلط بينها هو العطل:
 *   - `null`  = **بلا حدّ** (super-admin · أو مستخدم عليه `all_warehouses`)
 *   - `[]`    = **لا يرى شيئاً** (صاحب صلاحية بلا مخزن مرتبط)
 *   - `[...]` = مخازنه
 *   إرجاع `null` لمن لا مخزن له يفتح المنظومة كلها — وهو الدرس المدفوع
 *   ثمنه في موديول رأي المواطن (`FeedbackAccess::governorateIds`).
 *
 * ⚠️ والمنسدلات تُفلتر كما تُفلتر النتائج. لولا ذلك لتسرّبت أسماء مخازن
 *    محافظاتٍ أخرى لمن يعدّل الرابط، وإن كانت أرقامها محجوبة.
 */
class WarehouseScope
{
    /**
     * معرّفات مخازن المستخدم — `null` لبلا حدّ، `[]` لبلا رؤية.
     *
     * @return array<int, int>|null
     */
    public static function warehouseIds(?Authenticatable $user = null): ?array
    {
        $user = $user ?? Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        // التجاوز يعطي رؤيةً كاملة — والمخزن الجديد يدخلها تلقائياً
        if ($user->hasRole('super-admin') || $user->all_warehouses) {
            return null;
        }

        return $user->warehouses()->pluck('warehouses.id')->all();
    }

    /** هل نطاق المستخدم بلا حدّ؟ */
    public static function unlimited(?Authenticatable $user = null): bool
    {
        return self::warehouseIds($user) === null;
    }

    /**
     * يُقيّد استعلاماً بعمود معرّف مخزن.
     *
     * @param  string  $column  العمود الحامل لمعرّف المخزن (مؤهَّلاً باسم جدوله)
     */
    public static function apply(Builder $query, string $column = 'warehouses.id', ?Authenticatable $user = null): Builder
    {
        $ids = self::warehouseIds($user);

        if ($ids === null) {
            return $query;
        }

        // ⚠️ `whereIn` بمصفوفة فارغة يُخرج صفراً من الصفوف — وهو المطلوب
        //    بالضبط لمن لا مخزن له: لا يرى شيئاً، لا يرى الكل.
        return $query->whereIn($column, $ids);
    }

    /**
     * يُقيّد استعلاماً يقع فيه المخزن على أحد طرفين (النقل: مصدر أو مستلم).
     *
     * ⚠️ الصفّ يدخل إن كان **أيٌّ** من الطرفين في نطاق المستخدم: نقلٌ من
     *    الرئيسي إلى مخزنه يخصّه وإن لم يملك الرئيسي.
     */
    public static function applyEither(Builder $query, string $first, string $second, ?Authenticatable $user = null): Builder
    {
        $ids = self::warehouseIds($user);

        if ($ids === null) {
            return $query;
        }

        return $query->where(fn ($q) => $q->whereIn($first, $ids)->orWhereIn($second, $ids));
    }

    /** مخازن المستخدم بترتيب العرض الموحّد — لمنسدلات الاختيار. */
    public static function warehouses(?Authenticatable $user = null)
    {
        return self::apply(Warehouse::query()->ordered(), 'warehouses.id', $user)->get();
    }

    /**
     * مخازن المحافظات المعطاة — **التعريف الواحد** لقاعدة «مخازن محافظاته».
     *
     * يقرأه موضعان: الملء التلقائي في فورم المستخدم، والربط الجماعي من شاشة
     * الأدوار. وتعريفان لقاعدةٍ واحدة يفترقان عند أول تعديل.
     *
     * ⚠️ و«المخزن الرئيسي بالمصلحة» بلا محافظة، فلا تبلغه هذه القاعدة أبداً —
     *    ولا يُربط إلا بيد المدير، وهو الصواب.
     *
     * @param  array<int, int|string>  $governorateIds
     * @return array<int, int>
     */
    public static function warehouseIdsForGovernorates(array $governorateIds): array
    {
        if ($governorateIds === []) {
            return [];
        }

        return Warehouse::whereIn('governorate_id', array_map('intval', $governorateIds))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * هل في نطاق المستخدم مخزن رئيسي (level=1)؟
     *
     * الوارد توريدُ مورّدٍ من خارج المنظومة، ولا يُسجَّل إلا على الرئيسي
     * (Incoming\Create يرفض غيره صراحةً). فمَن لا رئيسيَّ في نطاقه شاشةُ
     * الوارد عليه **فارغة أبداً** لا فارغة اليوم — وبندٌ كهذا في المنيو
     * يعلّم المستخدم أن المنظومة معطّلة، لا أن هذا ليس عمله.
     *
     * ⚠️ والمقياس **النطاق لا الصلاحية**: أمين المخزن الرئيسي يسجّل الوارد،
     *    وقد يأتي دورٌ يطالعه ولا يسجّله — والقراءة حقُّ مَن في نطاقه.
     * ⚠️ وبلا حدّ = نعم: الرئيسي داخلٌ فيه، ويدخله المخزن الجديد تلقائياً.
     */
    public static function hasMainWarehouse(?Authenticatable $user = null): bool
    {
        $ids = self::warehouseIds($user);

        if ($ids === null) {
            return true;
        }

        if ($ids === []) {
            return false;
        }

        return Warehouse::whereIn('warehouses.id', $ids)
            ->whereHas('type', fn ($q) => $q->where('level', 1))
            ->exists();
    }

    /** هل يملك المستخدم هذا المخزن في نطاقه؟ (لحارس فعلٍ على مخزن بعينه) */
    public static function allows(int $warehouseId, ?Authenticatable $user = null): bool
    {
        $ids = self::warehouseIds($user);

        return $ids === null || in_array($warehouseId, $ids, true);
    }
}
