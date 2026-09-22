<?php

namespace App\Livewire\Contractors\Reports;

use App\Exports\ContractorsAttendanceExport;
use App\Support\Contractors\AttendanceReport;
use App\Support\ContractorScope;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

/**
 * تقرير المحافظة — صفٌّ لكل محافظة بأعداد عامليها وأيامهم، وصفُّ إجمالي.
 *
 * وهو **البيان العددي** نفسه: «عدد العاملين» عمودٌ فيه، والإجمالي في تذييله —
 * فلا شاشة رابعة تُبنى لرقمٍ يعيش هنا أصلاً.
 */
#[Layout('layouts.app')]
#[Title('تقرير المحافظات')]
class GovernorateReport extends Component
{
    use Concerns\BuildsAttendanceReport;

    /** محافظاتٌ بعينها، والفارغ = كل نطاق المستخدم. */
    public array $governorateIds = [];

    protected function appliedFilters(): array
    {
        return ['governorateIds' => array_values(array_filter(array_map('intval', $this->governorateIds)))];
    }

    protected function filterKeys(): array
    {
        return ['governorateIds'];
    }

    protected function reportLevel(): string
    {
        return 'governorates';
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

        $rows    = $this->buildRows();
        $columns = AttendanceReport::statusColumns($rows);

        return Excel::download(
            new ContractorsAttendanceExport(
                rows: AttendanceReport::groupBy($rows, 'governorate_id'),
                statuses: $columns,
                subjectLabel: __('home.governorate_name'),
                subjectKey: 'governorate_name',
                withContractorCount: true,
                title: __('home.ct_rep_governorates_title'),
                period: $this->periodLabel(),
                breakdown: $this->query()?->report()?->breakdown() ?? []
            ),
            $this->exportFileName('contractors-governorates')
        );
    }

    public function render()
    {
        $rows    = $this->hasSearched ? $this->buildRows() : [];
        $groups  = AttendanceReport::groupBy($rows, 'governorate_id');
        $report  = $this->query()?->report();

        // ترتيب المحافظات ترتيبَها التنظيمي لا ترتيبَ ظهورها في الصفوف.
        $order = ContractorScope::governorateOptions()->pluck('name', 'id');
        uksort($groups, fn ($a, $b) => $order->keys()->search($a) <=> $order->keys()->search($b));

        return view('livewire.contractors.reports.governorates', [
            'governorates' => ContractorScope::governorateOptions(),
            'groups'       => $groups,
            'statuses'     => AttendanceReport::statusColumns($rows),
            'totals'       => AttendanceReport::sum($rows),
            // عددُ عاملين لا أيام — التنبيه يقول «مَن» لا «كم يوماً»
            'unrecorded'   => AttendanceReport::unrecordedContractors($rows),
            'contractors'  => count(array_unique(array_column($rows, 'contractor_id'))),
            'breakdown'    => $this->hasSearched && $report ? $report->breakdown() : null,
            'holidays'     => $this->hasSearched && $report ? $report->holidays() : [],
            'names'        => $order,
        ]);
    }
}
