<?php

namespace App\Livewire\Contractors\Reports;

use App\Exports\ContractorsAttendanceExport;
use App\Models\AttendanceDay;
use App\Models\AttendanceStatus;
use App\Models\Contractor;
use App\Support\Contractors\AttendanceReport;
use App\Support\ContractorScope;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

/**
 * تقرير العامل — صفٌّ لكل مدة تسكين في المدى، **ومعه تواريخ غيابه وإجازاته**.
 *
 * تفصيل التواريخ هو ما يميّز هذا التقرير عن تقرير المقر: «غاب ٢» رقمٌ يُراجَع،
 * و«غاب يوم ٢ ويوم ٧» واقعةٌ يُسأل عنها.
 */
#[Layout('layouts.app')]
#[Title('تقرير العامل')]
class ContractorReport extends Component
{
    use Concerns\BuildsAttendanceReport;

    public ?int $governorateId = null;

    public ?int $contractorId = null;

    /** بحثٌ داخل منسدلة العاملين — المحافظة الواحدة فيها مئات العاملين. */
    public string $contractorSearch = '';

    public function updatedGovernorateId(): void
    {
        $this->contractorId     = null;
        $this->contractorSearch = '';   // بحثٌ من محافظةٍ سابقة لا معنى له في الجديدة
    }

    protected function appliedFilters(): array
    {
        return [
            'governorateId' => $this->governorateId ? (int) $this->governorateId : null,
            'contractorId'  => $this->contractorId ? (int) $this->contractorId : null,
        ];
    }

    protected function filterKeys(): array
    {
        return ['governorateId', 'contractorId', 'contractorSearch'];
    }

    protected function reportLevel(): string
    {
        return 'contractor';
    }

    protected function appliedGovernorateIds(): array
    {
        return array_values(array_filter([$this->applied['governorateId'] ?? null]));
    }

    /** العامل المطلوب — يُقرأ عبر النطاق في الاستعلام المشترك. */
    protected function subject(): ?Contractor
    {
        return $this->query()?->subject();
    }

    /** تفصيل التواريخ — من الاستعلام المشترك، فالشاشة والمطبوع يعرضان الشيء نفسه. */
    public function exceptionDates(array $rows): array
    {
        return $this->query()?->exceptionDates($rows) ?? [];
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
                subjectLabel: __('home.ct_rep_office_col'),
                subjectKey: 'office_name',
                withContractorCount: false,
                title: __('home.ct_rep_contractor_title').' — '.($this->subject()?->name ?? ''),
                period: $this->periodLabel(),
                breakdown: $this->query()?->report()?->breakdown() ?? []
            ),
            $this->exportFileName('contractor-attendance')
        );
    }

    public function render()
    {
        $rows   = $this->hasSearched ? $this->buildRows() : [];
        $report = $this->query()?->report();

        return view('livewire.contractors.reports.contractor', [
            'governorates' => ContractorScope::governorateOptions(),
            'candidates'   => $this->searchOptions($this->contractorOptions(), $this->contractorSearch, $this->contractorId),
            'subject'      => $this->hasSearched ? $this->subject() : null,
            'rows'         => $rows,
            'statuses'     => AttendanceReport::statusColumns($rows),
            'totals'       => AttendanceReport::sum($rows),
            'exceptions'   => $this->exceptionDates($rows),
            'breakdown'    => $this->hasSearched && $report ? $report->breakdown() : null,
            'holidays'     => $this->hasSearched && $report ? $report->holidays() : [],
        ]);
    }

    /** منسدلة العاملين — مقصورة على النطاق وعلى المحافظة المختارة. */
    protected function contractorOptions()
    {
        $query = Contractor::query();

        ContractorScope::applyToContractors($query);

        if ($this->governorateId) {
            $query->whereHas(
                'assignments',
                fn ($q) => $q->whereHas('office', fn ($o) => $o->where('governorate_id', $this->governorateId))
            );
        }

        return $query->orderBy('name')->orderBy('id')->get(['id', 'name', 'phone']);
    }
}
