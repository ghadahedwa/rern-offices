<?php

namespace App\Support\FeedbackResults;

use App\Models\FeedbackSuggestion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * تقرير «آراء المواطنين»: التقييمات والمقترحات في جدول واحد — بيانات المواطن
 * والمقر والمحافظة والرأي المكتوب (ملاحظة التقييم · «اكتب اقتراحك»).
 *
 * ⚠️ كل نصف يُبنى من كلاس استعلام شاشته (RatingsQuery / SuggestionsQuery) لا من جديد:
 *    النطاق والفلاتر والبحث والسلة هناك، فلا يخرج التقرير بصفوف تخالف الشاشتين.
 *
 * الاتحاد يُلفّ في استعلام خارجي يضمّ أسماء المقر والمحافظة — الضمّ داخل كل نصف
 * كان سيجعل أعمدة الفلاتر غير المؤهَّلة (name · created_at · office_id) ملتبسة.
 */
final class OpinionsQuery
{
    public const TYPES = ['rating', 'suggestion'];

    /** النوع من الرابط بقائمة بيضاء — القيمة المجهولة = الكل. */
    public static function type(string $type): string
    {
        return in_array($type, self::TYPES, true) ? $type : '';
    }

    public static function build(
        FeedbackFilterSet $filters,
        ?User $user = null,
        string $search = '',
        string $type = '',
        string $dir = 'desc',
    ): Builder {
        $type  = self::type($type);
        $parts = [];

        if ($type !== 'suggestion') {
            $parts[] = RatingsQuery::build($filters, $user, $search)->toBase()->select([
                DB::raw("'rating' as kind"), 'id', 'created_at', 'governorate_id', 'office_id',
                'name', 'national_id', 'phone', 'overall_rating', DB::raw('notes as opinion'),
            ]);
        }

        if ($type !== 'rating') {
            $parts[] = SuggestionsQuery::build($filters, $user, $search)->toBase()->select([
                DB::raw("'suggestion' as kind"), 'id', 'created_at', 'governorate_id', 'office_id',
                'name', 'national_id', 'phone', DB::raw('NULL as overall_rating'), DB::raw('other_suggestion as opinion'),
            ]);
        }

        $union = array_shift($parts);
        foreach ($parts as $part) {
            $union->unionAll($part);
        }

        return DB::query()->fromSub($union, 'o')
            ->leftJoin('offices', 'offices.id', '=', 'o.office_id')
            ->leftJoin('governorates', 'governorates.id', '=', 'o.governorate_id')
            ->select('o.*', DB::raw('offices.name as office_name'), DB::raw('governorates.name as governorate_name'))
            // النوع والمعرّف مُرجِّحان حتى يثبت ترتيب الصفوف المتساوية في الوقت بين الصفحات
            ->orderBy('o.created_at', $dir === 'asc' ? 'asc' : 'desc')
            ->orderBy('o.kind')
            ->orderBy('o.id');
    }

    /**
     * عناوين الكتالوج المختارة لمقترحات الصفوف — استعلام واحد للصفحة كلها،
     * والعنوان مكمّل للمكتوب: مقترحٌ بلا نص ليس مقترحاً فارغاً.
     *
     * @return array<int, string> معرّف المقترح ← العناوين مفصولة بفاصلة
     */
    public static function topicsFor(Collection $rows): array
    {
        $ids = $rows->where('kind', 'suggestion')->pluck('id')->all();

        if ($ids === []) {
            return [];
        }

        return FeedbackSuggestion::withTrashed()->with('topics:id,name')->whereKey($ids)->get()
            ->mapWithKeys(fn ($s) => [$s->id => $s->topics->pluck('name')->implode('، ')])
            ->all();
    }

    /**
     * الصفّ الخام يحمل created_at نصاً (لا كاست بلا موديل) — يُحوَّل لحظةً بتوقيت
     * التخزين (UTC) حتى يعرضها LocalTime بتوقيت القاهرة كباقي الشاشات.
     */
    public static function hydrate(object $row): object
    {
        $row->created_at = $row->created_at !== null
            ? CarbonImmutable::parse($row->created_at, config('app.timezone'))
            : null;

        return $row;
    }

    /** «مجهول» = بلا رقم قومي وبلا هاتف — تعريف HasFeedbackIdentity نفسه على صفّ خام. */
    public static function isAnonymous(object $row): bool
    {
        return $row->national_id === null && $row->phone === null;
    }
}
