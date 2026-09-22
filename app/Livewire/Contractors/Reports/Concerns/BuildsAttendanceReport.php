<?php

namespace App\Livewire\Contractors\Reports\Concerns;

use App\Models\Contractor;
use App\Support\Contractors\AttendanceReport;
use App\Support\Contractors\AttendanceReportQuery;
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
     * استعلام التقرير على الفلاتر المطبَّقة — **الشاشة والملف والتقرير المطبوع
     * يقرأون منه جميعاً**، فلا يخرج ملفٌّ بأرقامٍ تخالف الشاشة.
     */
    protected function query(): ?AttendanceReportQuery
    {
        $from = AttendanceReportQuery::parseDate($this->applied["from"] ?? null);
        $to   = AttendanceReportQuery::parseDate($this->applied["to"] ?? null);

        if (! $from || ! $to) {
            return null;
        }

        return new AttendanceReportQuery(
            level: $this->reportLevel(),
            from: $from,
            to: $to,
            governorateIds: $this->appliedGovernorateIds(),
            officeId: $this->applied["officeId"] ?? null,
            contractorId: $this->applied["contractorId"] ?? null,
            user: auth()->user(),
        );
    }

    protected function buildRows(): array
    {
        return $this->query()?->rows() ?? [];
    }

    /** رابط تقرير الـPDF — الفلاتر في الـquery string فالرابط قابل للمشاركة والحفظ. */
    public function openPdf(): void
    {
        if (! $this->guardExport()) {
            return;
        }

        $query = $this->query();

        if (! $query) {
            return;
        }

        $url = route("contractors.reports.pdf", $query->toQuery());

        $this->js("window.open('".$url."', '_blank')");
    }

    /** تصفية منسدلةٍ طويلة — التطبيع العربي في الكلاس المشترك. */
    protected function searchOptions(iterable $options, string $term, int|string|null $selected = null): array
    {
        return AttendanceReportQuery::filterOptions($options, $term, $selected);
    }

    /** مستوى التقرير — يحدّد شكل الصفوف وسطر الفلتر المطبوع. */
    abstract protected function reportLevel(): string;

    /** @return array<int,int> محافظاتٌ محدَّدة، والفارغ = كل نطاق المستخدم */
    abstract protected function appliedGovernorateIds(): array;
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
