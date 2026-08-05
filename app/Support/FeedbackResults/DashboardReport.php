<?php

namespace App\Support\FeedbackResults;

use App\Models\FeedbackRating;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\SuggestionTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * حسابات لوحة نتائج رأي المواطن.
 *
 * تعيش خارج المكوّن حتى تقرأها الشاشة وتقرير الـ PDF/Excel من مصدر واحد —
 * تقرير مطبوع بأرقام تخالف الشاشة أسوأ من غياب التقرير.
 *
 * قواعد لا تُكسر (مغطّاة باختبارات):
 *  - المحور السادس nullable: AVG/COUNT في SQL يتجاهلان NULL فيُحسب على المجيبين فقط.
 *  - حد أدنى للعينة قبل ترتيب المقر: مقر بتقييم واحد بخمس نجوم ليس أفضل مقر.
 *  - التقييم العام ومتوسط المحاور رقمان مختلفان، يُعرضان معاً.
 */
final class DashboardReport
{
    public function __construct(
        private readonly FeedbackFilterSet $filters,
        private readonly ?User $user = null,
    ) {}

    public function filters(): FeedbackFilterSet
    {
        return $this->filters;
    }

    /** الحد الأدنى للعينة قبل ترتيب المقر. */
    public function minSample(): int
    {
        return max(1, (int) config('feedback.min_ratings_for_ranking', 5));
    }

    public function ratingsQuery(): Builder
    {
        return RatingsQuery::build($this->filters, $this->user);
    }

    public function suggestionsQuery(): Builder
    {
        return SuggestionsQuery::build($this->filters, $this->user);
    }

    /** كل ما تعرضه الشاشة ويطبعه التقرير — مفاتيح واحدة للاثنين. */
    public function all(): array
    {
        return [
            'kpis'       => $this->kpis(),
            'criteria'   => $this->criteriaAverages(),
            'waits'      => $this->waitDistribution(),
            'ranking'    => $this->officesRanking(),
            'govRanking' => $this->governoratesRanking(),
            'trend'      => $this->monthlyTrend(),
            'priority'   => $this->topicsPriority(),
            'freeTexts'  => $this->freeTexts(),
            'rejected'   => $this->rejectedSummary(),
            'minSample'  => $this->minSample(),
        ];
    }

    /** بطاقات المؤشرات الأربعة. */
    public function kpis(): array
    {
        $agg = $this->ratingsQuery()
            ->selectRaw('COUNT(*) as total, AVG(overall_rating) as avg_overall, COUNT(DISTINCT office_id) as offices')
            ->first();

        return [
            'ratings'       => (int) ($agg->total ?? 0),
            'suggestions'   => $this->suggestionsQuery()->count(),
            'avg_overall'   => $agg->avg_overall !== null ? round((float) $agg->avg_overall, 2) : null,
            'rated_offices' => (int) ($agg->offices ?? 0),
        ];
    }

    /**
     * متوسط كل محور مع عدد المجيبين.
     * AVG/COUNT في SQL يتجاهلان NULL، فالمحور الاختياري يُحسب على مَن أجاب فقط.
     */
    public function criteriaAverages(): array
    {
        $select = [];
        foreach (array_keys(FeedbackRating::CRITERIA) as $field) {
            $select[] = "AVG({$field}) as avg_{$field}";
            $select[] = "COUNT({$field}) as cnt_{$field}";
        }

        $row = $this->ratingsQuery()->selectRaw(implode(', ', $select))->first();

        $out = [];
        foreach (FeedbackRating::CRITERIA as $field => [$label, $optional]) {
            $avg = $row?->{"avg_{$field}"};
            $out[] = [
                'label'    => $label,
                'optional' => $optional,
                'avg'      => $avg !== null ? round((float) $avg, 2) : null,
                'count'    => (int) ($row?->{"cnt_{$field}"} ?? 0),
            ];
        }

        return $out;
    }

    /** توزيع مدة الانتظار بالترتيب المعرَّف في الموديل. */
    public function waitDistribution(): array
    {
        $counts = $this->ratingsQuery()
            ->selectRaw('wait_time, COUNT(*) as total')
            ->groupBy('wait_time')
            ->pluck('total', 'wait_time');

        $sum = max(1, (int) $counts->sum());

        $out = [];
        foreach (FeedbackRating::WAIT_TIMES as $key => $label) {
            $count = (int) ($counts[$key] ?? 0);
            $out[] = [
                'label'   => $label,
                'count'   => $count,
                'percent' => round($count * 100 / $sum),
            ];
        }

        return $out;
    }

    /**
     * ترتيب المقرات حسب متوسط التقييم العام.
     * المقرات دون الحد الأدنى للعينة تُفصل في مجموعة مستقلة دون ترتيب.
     */
    public function officesRanking(): array
    {
        $rows = $this->ratingsQuery()
            ->whereNotNull('office_id')
            ->selectRaw('office_id, COUNT(*) as total, AVG(overall_rating) as avg_overall')
            ->groupBy('office_id')
            ->get();

        $names = Office::whereIn('id', $rows->pluck('office_id'))->pluck('name', 'id');

        $mapped = $rows->map(fn ($r) => [
            'office' => $names[$r->office_id] ?? __('home.fr_deleted_office'),
            'count'  => (int) $r->total,
            'avg'    => round((float) $r->avg_overall, 2),
        ]);

        $min    = $this->minSample();
        $ranked = $mapped->where('count', '>=', $min)->sortByDesc('avg')->values();

        return [
            'top'          => $ranked->take(5)->values(),
            'bottom'       => $ranked->reverse()->take(5)->values(),
            'insufficient' => $mapped->where('count', '<', $min)->sortByDesc('count')->take(10)->values(),
            'ranked_count' => $ranked->count(),
        ];
    }

    /**
     * كل المقرات المقيَّمة بمتوسطها وعدد آرائها، معلَّم عليها ما دون حد العينة.
     * الشاشة تعرض أعلى/أدنى خمسة فقط لأن المساحة محدودة؛ الملف المصدَّر لا يحتمل
     * هذا الاختصار — مَن يفتحه يريد الصورة كاملة ليفرزها بنفسه.
     */
    public function officesTable()
    {
        $rows = $this->ratingsQuery()
            ->whereNotNull('office_id')
            ->selectRaw('office_id, COUNT(*) as total, AVG(overall_rating) as avg_overall')
            ->groupBy('office_id')
            ->get();

        $offices = Office::with('governorate:id,name')
            ->whereIn('id', $rows->pluck('office_id'))->get(['id', 'name', 'governorate_id']);
        $min = $this->minSample();

        return $rows->map(fn ($r) => [
            'office'      => $offices->firstWhere('id', $r->office_id)?->name ?? __('home.fr_deleted_office'),
            'governorate' => $offices->firstWhere('id', $r->office_id)?->governorate?->name ?? '—',
            'count'       => (int) $r->total,
            'avg'         => round((float) $r->avg_overall, 2),
            'enough'      => (int) $r->total >= $min,
        ])->sortByDesc('avg')->values();
    }

    /** ترتيب المحافظات حسب متوسط التقييم — نفس حد العينة المطبَّق على المقرات. */
    public function governoratesRanking()
    {
        $rows = $this->ratingsQuery()
            ->whereNotNull('governorate_id')
            ->selectRaw('governorate_id, COUNT(*) as total, AVG(overall_rating) as avg_overall')
            ->groupBy('governorate_id')
            ->get();

        $names = Governorate::whereIn('id', $rows->pluck('governorate_id'))->pluck('name', 'id');
        $min   = $this->minSample();

        return $rows->map(fn ($r) => [
            'governorate' => $names[$r->governorate_id] ?? '—',
            'count'       => (int) $r->total,
            'avg'         => round((float) $r->avg_overall, 2),
            'enough'      => (int) $r->total >= $min,
        ])->sortByDesc('avg')->values();
    }

    /**
     * الاتجاه الشهري: متوسط التقييم العام وعدد الآراء شهراً بشهر.
     * يجيب على «هل الخدمة بتتحسن؟» — وهو ما لا تقوله لقطة الفترة الواحدة.
     * بلا فلتر فترة نقتصر على آخر ١٢ شهراً حتى لا يتمدّد المخطط بلا حدود.
     */
    public function monthlyTrend(): array
    {
        $query = $this->ratingsQuery();

        if ($this->filters->from === '' && $this->filters->to === '') {
            $query->where('created_at', '>=', now()->subMonths(11)->startOfMonth());
        }

        $rows = $query->selectRaw($this->monthExpression().' as ym, COUNT(*) as total, AVG(overall_rating) as avg_overall')
            ->groupBy('ym')->orderBy('ym')->get();

        return $rows->map(fn ($r) => [
            'label' => $r->ym,
            'count' => (int) $r->total,
            'avg'   => round((float) $r->avg_overall, 2),
        ])->all();
    }

    /** تجميع الشهر يختلف بين MySQL (الإنتاج) وsqlite (الاختبارات). */
    private function monthExpression(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";
    }

    /**
     * أكثر عناوين المقترحات طلباً + توزيع المجالات.
     * نعدّ على جدول الربط مقيَّداً بمقترحات النطاق المفلتَر (subquery لا تحميل كامل).
     */
    public function topicsPriority(): array
    {
        $counts = DB::table('feedback_suggestion_topic')
            ->whereIn('feedback_suggestion_id', $this->suggestionsQuery()->select('feedback_suggestions.id')->toBase())
            ->selectRaw('suggestion_topic_id, COUNT(*) as total')
            ->groupBy('suggestion_topic_id')
            ->pluck('total', 'suggestion_topic_id');

        if ($counts->isEmpty()) {
            return ['topics' => collect(), 'domains' => collect(), 'max' => 0];
        }

        $topics = SuggestionTopic::with('domain')->whereIn('id', $counts->keys())->get();

        $rows = $topics->map(fn ($t) => [
            'name'   => $t->name,
            'domain' => $t->domain?->name ?? '—',
            'count'  => (int) ($counts[$t->id] ?? 0),
        ])->sortByDesc('count')->values();

        $domains = $rows->groupBy('domain')
            ->map(fn ($group, $domain) => ['name' => $domain, 'count' => $group->sum('count')])
            ->sortByDesc('count')->values();

        return [
            'topics'  => $rows->take(10),
            'domains' => $domains,
            'max'     => (int) $rows->max('count'),
        ];
    }

    /** نصوص حرة: ملاحظات التقييمات + الاقتراحات الحرة (الأحدث أولاً). */
    public function freeTexts(int $limit = 6): array
    {
        $notes = $this->ratingsQuery()
            ->whereNotNull('notes')->where('notes', '!=', '')
            ->with('office:id,name')
            ->latest('created_at')->limit($limit)->get();

        $others = $this->suggestionsQuery()
            ->whereNotNull('other_suggestion')->where('other_suggestion', '!=', '')
            ->with('office:id,name')
            ->latest('created_at')->limit($limit)->get();

        return ['notes' => $notes, 'others' => $others];
    }

    /** ملخص المحاولات المرفوضة حسب السبب — نفس فلترة شاشة المرفوضات. */
    public function rejectedSummary()
    {
        return RejectedAttemptsQuery::build($this->filters, $this->user)
            ->selectRaw('reason, COUNT(*) as total')
            ->groupBy('reason')
            ->pluck('total', 'reason');
    }
}
