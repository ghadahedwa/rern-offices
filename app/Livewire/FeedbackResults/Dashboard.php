<?php

namespace App\Livewire\FeedbackResults;

use App\Livewire\FeedbackResults\Concerns\WithFeedbackFilters;
use App\Models\FeedbackRating;
use App\Models\FeedbackRejectedAttempt;
use App\Models\FeedbackSuggestion;
use App\Models\Office;
use App\Models\SuggestionTopic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('لوحة نتائج رأي المواطن')]
class Dashboard extends Component
{
    use WithFeedbackFilters;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
    }

    /** الحد الأدنى للعينة قبل ترتيب المقر. */
    public function minSample(): int
    {
        return max(1, (int) config('feedback.min_ratings_for_ranking', 5));
    }

    private function ratingsQuery(): Builder
    {
        return $this->applyFilters(FeedbackRating::query());
    }

    private function suggestionsQuery(): Builder
    {
        return $this->applyFilters(FeedbackSuggestion::query());
    }

    /** بطاقات المؤشرات الأربعة. */
    private function kpis(): array
    {
        $agg = $this->ratingsQuery()
            ->selectRaw('COUNT(*) as total, AVG(overall_rating) as avg_overall, COUNT(DISTINCT office_id) as offices')
            ->first();

        return [
            'ratings'      => (int) ($agg->total ?? 0),
            'suggestions'  => $this->suggestionsQuery()->count(),
            'avg_overall'  => $agg->avg_overall !== null ? round((float) $agg->avg_overall, 2) : null,
            'rated_offices' => (int) ($agg->offices ?? 0),
        ];
    }

    /**
     * متوسط كل محور مع عدد المجيبين.
     * AVG/COUNT في SQL يتجاهلان NULL، فالمحور الاختياري يُحسب على مَن أجاب فقط.
     */
    private function criteriaAverages(): array
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
    private function waitDistribution(): array
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
    private function officesRanking(): array
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

        $min = $this->minSample();
        $ranked = $mapped->where('count', '>=', $min)->sortByDesc('avg')->values();

        return [
            'top'          => $ranked->take(5)->values(),
            'bottom'       => $ranked->reverse()->take(5)->values(),
            'insufficient' => $mapped->where('count', '<', $min)->sortByDesc('count')->take(10)->values(),
            'ranked_count' => $ranked->count(),
        ];
    }

    /**
     * أكثر عناوين المقترحات طلباً + توزيع المجالات.
     * نعدّ على جدول الربط مقيَّداً بمقترحات النطاق المفلتَر (subquery لا تحميل كامل).
     */
    private function topicsPriority(): array
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
    private function freeTexts(): array
    {
        $notes = $this->ratingsQuery()
            ->whereNotNull('notes')->where('notes', '!=', '')
            ->with('office:id,name')
            ->latest('created_at')->limit(6)->get();

        $others = $this->suggestionsQuery()
            ->whereNotNull('other_suggestion')->where('other_suggestion', '!=', '')
            ->with('office:id,name')
            ->latest('created_at')->limit(6)->get();

        return ['notes' => $notes, 'others' => $others];
    }

    /** ملخص المحاولات المرفوضة حسب السبب (المحافظة عبر علاقة المقر). */
    private function rejectedSummary()
    {
        return FeedbackRejectedAttempt::query()
            ->when($this->governorate_id !== '', fn ($q) => $q->whereHas('office', fn ($o) => $o->where('governorate_id', $this->governorate_id)))
            ->when($this->office_id !== '', fn ($q) => $q->where('office_id', $this->office_id))
            ->when($this->from !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->to))
            ->selectRaw('reason, COUNT(*) as total')
            ->groupBy('reason')
            ->pluck('total', 'reason');
    }

    public function render()
    {
        return view('livewire.feedback-results.dashboard', [
            'kpis'      => $this->kpis(),
            'criteria'  => $this->criteriaAverages(),
            'waits'     => $this->waitDistribution(),
            'ranking'   => $this->officesRanking(),
            'priority'  => $this->topicsPriority(),
            'freeTexts' => $this->freeTexts(),
            'rejected'  => $this->rejectedSummary(),
            'minSample' => $this->minSample(),
        ]);
    }
}
