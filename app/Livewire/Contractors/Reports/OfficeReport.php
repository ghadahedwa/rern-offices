<?php

namespace App\Livewire\Contractors\Reports;

use App\Exports\ContractorsAttendanceExport;
use App\Support\Contractors\AttendanceReport;
use App\Support\ContractorScope;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

/**
 * تقرير المقر — صفٌّ لكل عامل خدم فيه خلال المدى: الكشف الذي يُطبع ويُرسل.
 *
 * ⚠️ **المقر يصل من الفورم فيُفحص على النطاق** (`scopedOfficeIds`) لا يُمرَّر كما جاء،
 *    ومقرٌّ خارج محافظات المستخدم يُخرج جدولاً فارغاً لا صفوف محافظةٍ ليست له.
 */
#[Layout('layouts.app')]
#[Title('تقرير المقر')]
class OfficeReport extends Component
{
    // ⚠️ `search()` من الـtrait يُعاد تعريفه هنا، فيُستعار باسمٍ آخر بدل تكرار جسده.
    use Concerns\BuildsAttendanceReport {
        search as protected runSearch;
    }

    /** محافظةٌ أو أكثر (طلب المستخدمة 2026-09-24 — كانت محافظةً واحدة). */
    public array $governorateIds = [];

    /** مقرٌّ أو أكثر (طلب المستخدمة 2026-09-27 — كان مقراً واحداً)، والفارغ = كل مقرات المحافظات. */
    public array $officeIds = [];

    /** بحثٌ داخل منسدلة المقرات — القائمة تبلغ مئات المقار في المحافظة الواحدة. */
    public string $officeSearch = '';

    /**
     * تغيير المحافظات يُسقط من المقارّ المختارة **ما خرج منها وحده** — وإلا بقي مقرُّ محافظةٍ
     * أُلغيت مختاراً بلا صفوف. وإضافة محافظةٍ لا تُضيّع مقاراً اختارها المستخدم.
     */
    public function updatedGovernorateIds(): void
    {
        $this->officeSearch = '';   // بحثٌ من تحديدٍ سابق قد يُخرج قائمةً فارغة بلا سببٍ ظاهر

        if ($this->officeIds !== []) {
            $valid           = ContractorScope::officeOptions($this->selectedGovernorateIds())->pluck('id')->all();
            $this->officeIds = array_values(array_intersect($this->selectedOfficeIds(), $valid));
        }
    }

    /** ⚠️ كالمحافظات: أرقامٌ خالصة وحدها، وانتماؤها للنطاق يُفحص في الاستعلام. */
    private function selectedOfficeIds(): array
    {
        return self::cleanIds($this->officeIds);
    }

    private static function cleanIds(array $ids): array
    {
        return array_values(array_unique(array_map('intval', array_filter(
            $ids,
            fn ($id) => is_int($id) || (is_string($id) && ctype_digit($id))
        ))));
    }

    /** ⚠️ المعرّفات تصل من العميل: أرقامٌ خالصة وحدها، والنطاق يُطبَّق بعدها في الاستعلام. */
    private function selectedGovernorateIds(): array
    {
        return self::cleanIds($this->governorateIds);
    }

    /**
     * ⚠️ **المحافظة إلزامية هنا وحدها** (طلب المستخدمة 2026-09-22): بلا تحديدٍ يجمع
     *    التقرير عاملي **كل مقرات النطاق** فيخرج جدولٌ بمئات الصفوف لا يقرؤه أحد —
     *    وهو تقرير **مقر** لا تقرير جمهورية. وتقريرا المحافظات والعامل على حالهما.
     *    واختيار أكثر من محافظة مسموح (2026-09-24) — الحجم هنا باختيار المستخدم لا بغفلته.
     *
     * والرفض **يُصفّر المعروض** لا يُبقيه: نتيجةٌ قديمة تحت محدداتٍ جديدة تُقرأ على
     * أنها نتيجتها.
     */
    public function search(): void
    {
        if ($this->selectedGovernorateIds() === []) {
            Flux::toast(variant: 'warning', text: __('home.ct_rep_need_governorate'));

            $this->applied     = [];
            $this->hasSearched = false;

            return;
        }

        $this->runSearch();
    }

    protected function appliedFilters(): array
    {
        return [
            'governorateIds' => $this->selectedGovernorateIds(),
            'officeIds'      => $this->selectedOfficeIds(),
        ];
    }

    protected function filterKeys(): array
    {
        return ['governorateIds', 'governorateSearch', 'officeIds', 'officeSearch'];
    }

    protected function reportLevel(): string
    {
        return 'office';
    }

    protected function appliedGovernorateIds(): array
    {
        return $this->applied['governorateIds'] ?? [];
    }

    public function exportExcel()
    {
        if (! $this->guardExport()) {
            return;
        }

        $rows = $this->buildRows();

        return Excel::download(
            new ContractorsAttendanceExport(
                rows: $rows,
                statuses: AttendanceReport::statusColumns($rows),
                subjectLabel: __('home.ct_rep_contractor'),
                subjectKey: 'contractor_name',
                withContractorCount: false,
                title: __('home.ct_rep_offices_title'),
                period: $this->periodLabel(),
                breakdown: $this->query()?->report()?->breakdown() ?? [],
                secondLabel: __('home.ct_rep_office_col'),
                secondKey: 'office_name'
            ),
            $this->exportFileName('contractors-office')
        );
    }

    public function render()
    {
        $rows   = $this->hasSearched ? $this->buildRows() : [];
        $report = $this->query()?->report();

        return view('livewire.contractors.reports.office', [
            'governorateChoices' => $this->governorateChoices($this->governorateIds),
            'offices'      => $this->searchOptions(
                // بلا محافظة لا مقرات: القائمة كانت ستعرض مقرات النطاق كله والتقرير يرفضه
                $this->selectedGovernorateIds() ? ContractorScope::officeOptions($this->selectedGovernorateIds()) : collect(),
                $this->officeSearch,
                $this->selectedOfficeIds()
            ),
            'rows'         => $rows,
            'statuses'     => AttendanceReport::statusColumns($rows),
            'totals'       => AttendanceReport::sum($rows),
            // عددُ عاملين لا أيام — التنبيه يقول «مَن» لا «كم يوماً»
            'unrecorded'   => AttendanceReport::unrecordedContractors($rows),
            'contractors'  => count(array_unique(array_column($rows, 'contractor_id'))),
            'breakdown'    => $this->hasSearched && $report ? $report->breakdown() : null,
            'holidays'     => $this->hasSearched && $report ? $report->holidays() : [],
        ]);
    }
}
