<?php

namespace App\Livewire\Contractors\Reports;

use App\Support\Contractors\DashboardReport;
use App\Support\ContractorScope;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * لوحة فرع العاملين بالتعاقد — **صفحة دخول الفرع** (قرار العميل).
 *
 * التوزيع والأعداد وحضور الفترة، **بلا سجل نشاط المستخدمين** (قرار العميل) — وهو
 * ما يميّزها عن لوحة المقرات التي تعرض «مَن فعل ماذا» و«المتصلون الآن».
 *
 * ⚠️ **تقرأ من `AttendanceReportQuery` نفسه الذي تقرأ منه التقارير الثلاثة** عبر
 *    الطبقة المشتركة، فلا تعرض اللوحة رقم حضورٍ يخالف تقرير الفترة نفسها.
 */
#[Layout('layouts.app')]
#[Title('لوحة العاملين بالتعاقد')]
class BranchDashboard extends Component
{
    /*
     * ⚠️ **اللوحة تعرض فوراً لا بعد ضغط زرّ**: الطبقة المشتركة مبنيّة على «حدّد ثم
     *    اعرض» (`hasSearched`)، وهي صحيحة لتقريرٍ ثقيل وخاطئة للوحةٍ تُفتح للنظرة
     *    الأولى. فالتطبيق يقع في `mount()` ومع كل تغيّر للمدى.
     */
    use Concerns\BuildsAttendanceReport {
        mount as private initReport;
        search as private runSearch;
        applyPeriod as private setPeriod;
    }

    /** محافظاتٌ محدَّدة، والفارغ = كل نطاق المستخدم. */
    public array $governorateIds = [];

    public function mount(): void
    {
        $this->initReport();
        $this->runSearch();
    }

    public function updatedFrom(): void
    {
        $this->runSearch();
    }

    public function updatedTo(): void
    {
        $this->runSearch();
    }

    public function updatedGovernorateIds(): void
    {
        $this->runSearch();
    }

    public function applyPeriod(string $period): void
    {
        $this->setPeriod($period);
        $this->runSearch();
    }

    public function resetFilters(): void
    {
        $this->reset($this->filterKeys());
        $this->initReport();
        $this->runSearch();
    }

    protected function reportLevel(): string
    {
        // أرقام الحضور تُجمَّع على مستوى المحافظات — اللوحة لقطةٌ للفرع لا كشفُ مقر.
        return 'governorates';
    }

    protected function appliedGovernorateIds(): array
    {
        return $this->applied['governorateIds'] ?? [];
    }

    protected function appliedFilters(): array
    {
        return ['governorateIds' => array_values(array_filter(array_map('intval', $this->governorateIds)))];
    }

    protected function filterKeys(): array
    {
        return ['governorateIds'];
    }

    public function render()
    {
        $query  = $this->query();
        $report = $query ? new DashboardReport($query, auth()->user()) : null;

        $attendance = $report?->attendance() ?? ['working' => 0, 'present' => 0, 'unreviewed' => 0, 'exceptions' => [], 'unrecorded' => 0, 'statuses' => collect()];

        return view('livewire.contractors.reports.dashboard', [
            'governorates'  => ContractorScope::governorateOptions(),
            'headline'      => $report?->headline() ?? ['in_service' => 0, 'offices' => 0, 'governorates' => 0, 'archived' => 0],
            'byGovernorate' => $report?->byGovernorate() ?? [],
            'byProfession'  => $report?->byProfession() ?? [],
            'attendance'    => $attendance,
            'statuses'      => $attendance['statuses'],
            'unrecorded'    => $attendance['unrecorded'],
            'breakdown'     => $report?->breakdown(),
        ]);
    }
}
