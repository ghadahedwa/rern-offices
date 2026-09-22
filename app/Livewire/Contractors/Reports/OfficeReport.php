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

    public ?int $governorateId = null;

    public ?int $officeId = null;

    /** بحثٌ داخل منسدلة المقرات — القائمة تبلغ مئات المقار في المحافظة الواحدة. */
    public string $officeSearch = '';

    /** تغيير المحافظة يُصفّر المقر — وإلا بقي مقرُّ محافظةٍ أخرى مختاراً بلا صفوف. */
    public function updatedGovernorateId(): void
    {
        $this->officeId     = null;
        $this->officeSearch = '';   // بحثٌ من محافظةٍ سابقة لا معنى له في الجديدة
    }

    /**
     * ⚠️ **المحافظة إلزامية هنا وحدها** (طلب المستخدمة 2026-09-22): بلا تحديدٍ يجمع
     *    التقرير عاملي **كل مقرات النطاق** فيخرج جدولٌ بمئات الصفوف لا يقرؤه أحد —
     *    وهو تقرير **مقر** لا تقرير جمهورية. وتقريرا المحافظات والعامل على حالهما.
     *
     * والرفض **يُصفّر المعروض** لا يُبقيه: نتيجةٌ قديمة تحت محدداتٍ جديدة تُقرأ على
     * أنها نتيجتها.
     */
    public function search(): void
    {
        if (! $this->governorateId) {
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
            'governorateId' => $this->governorateId ? (int) $this->governorateId : null,
            'officeId'      => $this->officeId ? (int) $this->officeId : null,
        ];
    }

    protected function filterKeys(): array
    {
        return ['governorateId', 'officeId', 'officeSearch'];
    }

    protected function reportLevel(): string
    {
        return 'office';
    }

    protected function appliedGovernorateIds(): array
    {
        return array_values(array_filter([$this->applied['governorateId'] ?? null]));
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
            'governorates' => ContractorScope::governorateOptions(),
            'offices'      => $this->searchOptions(
                ContractorScope::officeOptions($this->governorateId),
                $this->officeSearch,
                $this->officeId
            ),
            'rows'         => $rows,
            'statuses'     => AttendanceReport::statusColumns($rows),
            'totals'       => AttendanceReport::sum($rows),
            'contractors'  => count(array_unique(array_column($rows, 'contractor_id'))),
            'breakdown'    => $this->hasSearched && $report ? $report->breakdown() : null,
            'holidays'     => $this->hasSearched && $report ? $report->holidays() : [],
        ]);
    }
}
