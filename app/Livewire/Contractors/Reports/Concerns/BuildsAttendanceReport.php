<?php

namespace App\Livewire\Contractors\Reports\Concerns;

use App\Models\Contractor;
use App\Support\Contractors\AttendanceReport;
use App\Support\ArabicText;
use App\Support\ContractorScope;
use App\Support\LocalTime;
use App\Support\WorkingDays;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Support\Collection;

/**
 * ما يشترك فيه تقارير الحضور الثلاثة: المدى الحرّ · الحراسة · بناء المحرّك · التصدير.
 *
 * ⚠️ **الأرقام كلها من `AttendanceReport`** — الشاشة لا تحسب شيئاً بنفسها، فلا يخرج
 *    ملفٌّ بأرقامٍ تخالف ما على الشاشة.
 * ⚠️ **لا صلاحية لمستوى التقرير** — النطاق (`governorate_user`) هو الحدّ: مَن له
 *    محافظتان يجد «الجمهورية» محافظتيه. والتصدير وحده بصلاحيته (`contractors.export`).
 */
trait BuildsAttendanceReport
{
    public string $from = '';

    public string $to = '';

    /** الفلاتر التي بُني عليها ما هو معروض — غيرُ الفلاتر الجاري تعديلها. */
    public array $applied = [];

    public bool $hasSearched = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('contractors.index'), 403);

        // ⚠️ «اليوم» بتوقيت القاهرة لا بـUTC — بين ١٢ و٣ فجراً يعطي `now()` تاريخ الأمس.
        $today = WorkingDays::today();

        $this->from = $today->startOfMonth()->toDateString();
        $this->to   = $today->toDateString();
    }

    /** اختصارات الفترة — أكثر ما يُطلب: الشهر الجاري والماضي والربع والسنة. */
    public function applyPeriod(string $period): void
    {
        $today = WorkingDays::today();

        [$from, $to] = match ($period) {
            'last_month'   => [$today->subMonth()->startOfMonth(), $today->subMonth()->endOfMonth()],
            'last_quarter' => [$today->subMonths(2)->startOfMonth(), $today],
            'this_year'    => [$today->startOfYear(), $today],
            default        => [$today->startOfMonth(), $today],
        };

        $this->from = $from->toDateString();
        $this->to   = $to->toDateString();
    }

    /**
     * تاريخٌ من الفورم — التالف يُهمَل ولا يُسقط الشاشة.
     */
    protected function parseDate(?string $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /** المحرّك على المدى المطبَّق — مصدر كل رقم في الشاشة والملف. */
    protected function report(): ?AttendanceReport
    {
        $from = $this->parseDate($this->applied['from'] ?? null);
        $to   = $this->parseDate($this->applied['to'] ?? null);

        return $from && $to ? new AttendanceReport($from, $to) : null;
    }

    /**
     * العاملون داخل نطاق المستخدم.
     *
     * 📌 **طبقة احتياط: لا يسقط اختبارٌ بحذف `applyToContractors` منها** (مُثبَتٌ
     *    بالكسر) — `scopedOfficeIds()` وحده يكفي لمنع التسرّب، لأن صفّاً لا يُبنى
     *    إلا لمقرٍّ مسموح. وتبقى هنا لأمرين: **تقليل المحمَّل** (لا تُسحب عمالة
     *    الجمهورية لتقرير محافظة)، **وبقاء الحدّ قائماً** لو نادى مستدعٍ لاحقٌ
     *    بـ`$officeIds = null` لمستخدمٍ محدود النطاق.
     *
     * @return Collection<int,Contractor>
     */
    protected function scopedContractors(?array $officeIds = null): Collection
    {
        $query = Contractor::query()->with('profession:id,name');

        ContractorScope::applyToContractors($query);

        if ($officeIds !== null) {
            $query->whereHas('assignments', fn ($q) => $q->whereIn('office_id', $officeIds));
        }

        return $query->orderBy('name')->orderBy('id')->get();
    }

    /**
     * المقارّ التي تُحتسب أيامها — **حارس تسرّب النطاق**، و`null` تعني بلا حدّ.
     *
     * ⚠️ `ContractorScope::applyToContractors` يُبقي العامل مرئياً بتسكينٍ واحدٍ داخل
     *    النطاق (عمداً: تقارير فتراته عندي تخصّني)، **فصفوفه تحمل معها تسكيناته في
     *    محافظاتٍ ليست لي**. فلولا قصرُ المقارّ هنا لظهرت في تقريري أيامُ عاملٍ نُقل
     *    إلى محافظةٍ أخرى — وهو تسرّبٌ صامت لا يشكو منه أحد.
     *
     * @param  array<int,mixed>  $selected  محافظاتٌ اختارها المستخدم (فارغة = كل نطاقه)
     */
    protected function scopedOfficeIds(array $selected = []): ?array
    {
        $scope    = ContractorScope::governorateIds();
        $selected = array_values(array_filter(array_map('intval', $selected)));

        if ($scope !== null) {
            // محافظةٌ تصل من الفورم وليست في النطاق تُهمَل ولا تُمرَّر.
            $selected = $selected === [] ? $scope : array_values(array_intersect($selected, $scope));

            if ($selected === []) {
                return [];
            }
        }

        if ($selected === []) {
            return null; // super-admin بلا تحديد = الجمهورية
        }

        return \App\Models\Office::query()->whereIn('governorate_id', $selected)->pluck('id')->all();
    }

    /**
     * تصفية خيارات منسدلةٍ طويلة بكلمة بحث.
     *
     * ⚠️ **بـ`ArabicText` لا `str_contains` مجرَّدة** — قاعدة البحث العربي في المشروع:
     *    تُوحَّد الألف والياء والتاء المربوطة وتُزال المسافات، فيجد المستخدم «مقر
     *    الاسماعيليه» بكتابة «الإسماعيلية».
     * ⚠️ **والخيار المختار يبقى في القائمة ولو لم يطابق البحث** — وإلا اختفى من
     *    المنسدلة فبدا للمستخدم أن اختياره ضاع، أو انتقل الاختيار إلى خيارٍ آخر صامتاً.
     *
     * @param  iterable<int,object>  $options  عناصر لها `id` و`name` (و`short_name` إن وُجد)
     * @return array<int, array{id:int, label:string, title:string}>
     */
    protected function searchOptions(iterable $options, string $term, int|string|null $selected = null): array
    {
        $needle = ArabicText::normalize($term);
        $out    = [];

        foreach ($options as $option) {
            $matches = $needle === ''
                || str_contains(ArabicText::normalize($option->name), $needle)
                || (int) $option->id === (int) $selected;

            if ($matches) {
                $out[] = [
                    'id'    => $option->id,
                    'label' => $option->short_name ?? $option->name,
                    'title' => $option->name,
                ];
            }
        }

        return $out;
    }

    /** وصفُ الفترة لرأس التقرير والملف — بتوقيت العرض لا بـUTC. */
    protected function periodLabel(): string
    {
        $from = $this->parseDate($this->applied['from'] ?? null);
        $to   = $this->parseDate($this->applied['to'] ?? null);

        if (! $from || ! $to) {
            return '';
        }

        return __('home.ct_rep_period_label', [
            'from' => LocalTime::date($from),
            'to'   => LocalTime::date($to),
        ]);
    }

    public function search(): void
    {
        $from = $this->parseDate($this->from);
        $to   = $this->parseDate($this->to);

        if (! $from || ! $to) {
            Flux::toast(variant: 'warning', text: __('home.ct_rep_bad_range'));

            return;
        }

        $this->applied     = $this->appliedFilters() + ['from' => $from->toDateString(), 'to' => $to->toDateString()];
        $this->hasSearched = true;
    }

    public function resetFilters(): void
    {
        $this->reset($this->filterKeys());
        $this->mount();
        $this->applied     = [];
        $this->hasSearched = false;
    }

    /**
     * ⚠️ **حارس التصدير في الإجراء لا في القالب**: التصدير يصل في طلبٍ مستقلٍّ عن
     *    `mount()`، والزرّ المخفيّ لا يمنع النداء. والملف يخرج من النظام فيُفحص
     *    بـ`export` لا بـ`index`.
     */
    protected function guardExport(): bool
    {
        if (! auth()->user()?->can('contractors.export')) {
            abort(403);
        }

        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.ct_rep_search_first'));

            return false;
        }

        return true;
    }

    protected function exportFileName(string $base): string
    {
        return $base.'-'.($this->applied['from'] ?? '').'-'.($this->applied['to'] ?? '').'.xlsx';
    }

    /** الفلاتر الخاصة بكل شاشة — تُضاف إلى المدى عند الضغط على «عرض». */
    abstract protected function appliedFilters(): array;

    /** الخصائص التي يمسحها زرّ المسح. */
    abstract protected function filterKeys(): array;
}
