<?php

namespace App\Support\FeedbackResults;

use App\Models\FeedbackDigitalRating;
use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * حسابات «ملخص المنصات» — الشاشة وتقريرا PDF/Excel يقرؤون منه معاً.
 *
 * قواعد لا تُكسر (مغطّاة باختبارات):
 *  - **مقام كل نسبة = مَن سُئل السؤال** (`scopeAsked` المشتقّ من SHOWN_WHEN)، لا كل الآراء.
 *  - **الصفر درجة في q205**: AVG يحسبه، والـNULL (لم يُسأل) خارج المتوسط.
 *  - **الاختيار المتعدد مجموع نسبه قد يتجاوز ١٠٠٪** — كل اختيار نسبته من المسؤولين.
 *  - مقارنة المقرات **بنسبة الحجز** (قرار المستخدمة)، والمقرات دون حدّ العينة خارج الترتيب.
 */
final class DigitalReport
{
    public function __construct(
        private readonly FeedbackFilterSet $filters,
        private readonly ?User $user = null,
    ) {}

    public function filters(): FeedbackFilterSet
    {
        return $this->filters;
    }

    public function minSample(): int
    {
        return max(1, (int) config('feedback.min_ratings_for_ranking', 5));
    }

    /** استعلام جديد في كل نداء — الـscopes تُعدِّل الـBuilder في مكانه. */
    public function query(): Builder
    {
        return DigitalRatingsQuery::build($this->filters, $this->user);
    }

    /** مَن سُئل السؤال ضمن النطاق المفلتر. */
    public function askedQuery(string $key): Builder
    {
        return $this->query()->asked($key);
    }

    /** كل ما تعرضه الشاشة ويطبعه التقرير — مفاتيح واحدة للجميع. */
    public function all(string $officeOrder = 'desc'): array
    {
        return [
            'headline'  => $this->headline(),
            'sections'  => $this->sections(),
            'score'     => $this->scale('q205'),
            'texts'     => ['q204' => $this->texts('q204'), 'q210' => $this->texts('q210')],
            'trend'     => $this->monthlyTrend(),
            'offices'   => $this->officesTable($officeOrder),
            'minSample' => $this->minSample(),
        ];
    }

    /** بطاقات المؤشرات — تقرؤها الشاشة وكرت اللوحة الرئيسية. */
    public function headline(): array
    {
        $total  = $this->query()->count();
        $booked = $this->query()->where('q201', 'booked')->count();

        $scoreBase = $this->askedQuery('q205')->whereNotNull('q205');
        $scoreAvg  = (clone $scoreBase)->avg('q205');

        $onTimeBase = $this->askedQuery('q206')->whereNotNull('q206')->count();
        $onTime     = $this->askedQuery('q206')->where('q206', 'on_time')->count();

        return [
            'total'           => $total,
            'booked'          => $booked,
            'booked_percent'  => $this->percent($booked, $total),
            'score_avg'       => $scoreAvg !== null ? round((float) $scoreAvg, 1) : null,
            'score_base'      => $scoreBase->count(),
            'on_time'         => $onTime,
            'on_time_base'    => $onTimeBase,
            'on_time_percent' => $this->percent($onTime, $onTimeBase),
        ];
    }

    /**
     * توزيعات أسئلة الاختيار بترتيب الاستمارة، مجمَّعة في محاور العرض.
     *
     * @return array<string, array<int, array>>
     */
    public function sections(): array
    {
        return [
            'booking'   => [$this->distribution('q201'), $this->distribution('q202'), $this->distribution('q203')],
            'visit'     => [$this->distribution('q206'), $this->distribution('q207')],
            'awareness' => [$this->distribution('q208'), $this->distribution('q209')],
            'ads'       => [$this->distribution('q211'), $this->distribution('q212'), $this->distribution('q213')],
        ];
    }

    /** توزيع سؤال اختيار واحد أو متعدد على مَن سُئله. */
    public function distribution(string $key): array
    {
        [$type, $question, $options] = FeedbackDigitalRating::QUESTIONS[$key];

        $base = $this->askedQuery($key)->count();

        // المتعدد مقيَّد بمَن سُئل كمقامه — طبقة احتياط: الاختيارات لا تُحفظ إلا لسؤالٍ ظهر،
        // فالعدّ على الكل يعطي الرقم نفسه اليوم (لا يسقط اختبارٌ بحذف القيد).
        $counts = $type === 'checkbox'
            ? DB::table('feedback_digital_choices')
                ->where('question', $key)
                ->whereIn('feedback_digital_rating_id', $this->askedQuery($key)->select('feedback_digital_ratings.id')->toBase())
                ->selectRaw('`option` as answer, COUNT(*) as total')
                ->groupBy('option')
                ->pluck('total', 'answer')
            : $this->askedQuery($key)
                ->whereNotNull($key)
                ->selectRaw("{$key} as answer, COUNT(*) as total")
                ->groupBy($key)
                ->pluck('total', 'answer');

        $rows = [];
        foreach ($options as $value => $label) {
            $count  = (int) ($counts[$value] ?? 0);
            $rows[] = ['label' => $label, 'count' => $count, 'percent' => $this->percent($count, $base)];
        }

        return [
            'key'      => $key,
            'title'    => __('home.fr_dg_'.$key),
            'question' => $question,
            'multi'    => $type === 'checkbox',
            'base'     => $base,
            'rows'     => $rows,
        ];
    }

    /** الدرجة من ٠ إلى ١٠: المتوسط والتوزيع والمقارنة بين المنصتين. */
    public function scale(string $key): array
    {
        [, $question, [$min, $max]] = FeedbackDigitalRating::QUESTIONS[$key];

        $answered = $this->askedQuery($key)->whereNotNull($key);
        $base     = (clone $answered)->count();
        $avg      = (clone $answered)->avg($key);

        $counts = (clone $answered)->selectRaw("{$key} as score, COUNT(*) as total")
            ->groupBy($key)->pluck('total', 'score');

        $distribution = [];
        for ($i = $min; $i <= $max; $i++) {
            $count          = (int) ($counts[$i] ?? 0);
            $distribution[] = ['score' => $i, 'count' => $count, 'percent' => $this->percent($count, $base)];
        }

        // المقارنة بين المنصتين — كل منصة على مَن حجز بها
        $platforms = FeedbackDigitalRating::QUESTIONS['q202'][2];
        $byPlatform = (clone $answered)->whereNotNull('q202')
            ->selectRaw("q202 as platform, COUNT(*) as total, AVG({$key}) as avg_score")
            ->groupBy('q202')->get()->keyBy('platform');

        $platformRows = [];
        foreach ($platforms as $value => $label) {
            $row            = $byPlatform[$value] ?? null;
            $platformRows[] = [
                'label' => $label,
                'count' => (int) ($row->total ?? 0),
                'avg'   => $row ? round((float) $row->avg_score, 1) : null,
            ];
        }

        return [
            'key'          => $key,
            'title'        => __('home.fr_dg_'.$key),
            'question'     => $question,
            'max'          => $max,
            'base'         => $base,
            'avg'          => $avg !== null ? round((float) $avg, 1) : null,
            'distribution' => $distribution,
            'platforms'    => $platformRows,
        ];
    }

    /**
     * النص الحر — قائمة للقراءة (قرار المستخدمة: عرض وبحث بلا تصنيف).
     * الشاشة تعرض الأحدث، والملف المصدَّر الكل (`$limit = null`).
     */
    public function texts(string $key, ?int $limit = 10): array
    {
        $written = $this->askedQuery($key)->whereNotNull($key)->where($key, '!=', '');

        return [
            'key'     => $key,
            'title'   => __('home.fr_dg_'.$key),
            'base'    => $this->askedQuery($key)->count(),
            'written' => (clone $written)->count(),
            'items'   => (clone $written)->with('office:id,name')
                ->latest('created_at')->latest('id')
                ->when($limit !== null, fn ($q) => $q->limit($limit))
                ->get(['id', 'office_id', $key, 'created_at'])
                ->map(fn ($r) => [
                    'text'   => $r->{$key},
                    'office' => $r->office?->name ?? __('home.fr_deleted_office'),
                    'date'   => $r->created_at,
                ]),
        ];
    }

    /**
     * الاتجاه الشهري: هل نسبة الحجز الإلكتروني تزيد؟ — السؤال الذي تُعمل الحملة من أجله.
     * بلا فلتر فترة يقتصر على آخر ١٢ شهراً (نفس قاعدة لوحة التقييمات).
     */
    public function monthlyTrend(): array
    {
        $query = $this->query();

        if ($this->filters->from === '' && $this->filters->to === '') {
            $query->where('created_at', '>=', now()->subMonths(11)->startOfMonth());
        }

        return $query
            ->selectRaw(DashboardReport::monthExpression()." as ym, COUNT(*) as total, SUM(CASE WHEN q201 = 'booked' THEN 1 ELSE 0 END) as booked, AVG(q205) as avg_score")
            ->groupBy('ym')->orderBy('ym')->get()
            ->map(fn ($r) => [
                'label'          => $r->ym,
                'count'          => (int) $r->total,
                'booked'         => (int) $r->booked,
                'booked_percent' => $this->percent((int) $r->booked, (int) $r->total),
                'score_avg'      => $r->avg_score !== null ? round((float) $r->avg_score, 1) : null,
            ])->all();
    }

    /**
     * مقارنة المقرات **بنسبة الحجز** — أيّ المقرات متأخرة في التحول الرقمي.
     * ذات العينة الكافية مرتّبة أولاً، وما دون الحد بعدها معلَّمة (لا تُرتَّب).
     *
     * ⚠️ الترتيب على النسبة الدقيقة لا المقرّبة، ثم عدد الآراء عند التساوي —
     *    ٦٦.٧٪ و٦٧٪ يقرّبان رقماً واحداً ولا يتساويان.
     */
    public function officesTable(string $order = 'desc'): Collection
    {
        $order = $order === 'asc' ? 'asc' : 'desc';

        $rows = $this->query()
            ->whereNotNull('office_id')
            ->selectRaw("office_id, COUNT(*) as total, SUM(CASE WHEN q201 = 'booked' THEN 1 ELSE 0 END) as booked, AVG(q205) as avg_score")
            ->groupBy('office_id')
            ->get();

        $offices = Office::with('governorate:id,name')
            ->whereIn('id', $rows->pluck('office_id'))->get(['id', 'name', 'governorate_id'])->keyBy('id');
        $min = $this->minSample();

        $mapped = $rows->map(fn ($r) => [
            'office'         => $offices->get($r->office_id)?->name ?? __('home.fr_deleted_office'),
            'governorate'    => $offices->get($r->office_id)?->governorate?->name ?? '—',
            'count'          => (int) $r->total,
            'booked'         => (int) $r->booked,
            'ratio'          => (int) $r->total > 0 ? (int) $r->booked / (int) $r->total : 0,
            'booked_percent' => $this->percent((int) $r->booked, (int) $r->total),
            'score_avg'      => $r->avg_score !== null ? round((float) $r->avg_score, 1) : null,
            'enough'         => (int) $r->total >= $min,
        ]);

        $sort = fn (Collection $c) => $c->sort(function ($a, $b) use ($order) {
            $byRatio = $order === 'asc' ? $a['ratio'] <=> $b['ratio'] : $b['ratio'] <=> $a['ratio'];

            return $byRatio !== 0 ? $byRatio : $b['count'] <=> $a['count'];
        })->values();

        return $sort($mapped->where('enough', true))->concat($sort($mapped->where('enough', false)))->values();
    }

    private function percent(int $count, int $base): ?float
    {
        return $base > 0 ? round($count * 100 / $base, 1) : null;
    }
}
