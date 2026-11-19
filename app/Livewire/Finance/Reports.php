<?php

namespace App\Livewire\Finance;

use App\Exports\FinanceReportExport;
use App\Models\FinanceUnit;
use App\Support\Finance\FinanceAccess;
use App\Support\Finance\FinanceCalendar;
use App\Support\Finance\FinanceReport;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

/**
 * تقارير الشئون المالية (المرحلة ٥): تقرير وحدة أو إجمالي الوحدات، لشهرٍ أو مدى شهورٍ أو سنةٍ مالية،
 * على الشاشة وPDF وExcel. الأرقام كلها من `FinanceReport` — والفلاتر في الرابط فالتقرير قابل للمشاركة.
 *
 * ⚠️ الحارس `finance.reports` في `mount()` **وفي التصدير** (طلبٌ مستقل) **وفي كنترولر الـPDF** (رابطٌ يُفتح منسوخاً).
 */
#[Layout('layouts.app')]
class Reports extends Component
{
    #[Url(as: 'kind')]
    public string $kind = 'unit';

    #[Url(as: 'unit')]
    public string $unit = '';

    #[Url(as: 'period')]
    public string $period = 'month';

    #[Url(as: 'month')]
    public string $month = '';

    #[Url(as: 'from')]
    public string $from = '';

    #[Url(as: 'to')]
    public string $to = '';

    #[Url(as: 'year')]
    public string $year = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(FinanceAccess::REPORTS), 403);

        if ($this->unit === '') {
            $this->unit = (string) (FinanceUnit::ordered()->value('id') ?? '');
        }
        $default = FinanceReport::defaultMonth()->format('Y-m');
        $this->month = $this->month ?: $default;
        $this->from  = $this->from ?: FinanceCalendar::firstMonth()->format('Y-m');
        $this->to    = $this->to ?: $default;
        $this->year  = $this->year ?: (string) FinanceCalendar::fiscalYearOf(FinanceCalendar::currentMonth());
    }

    protected function report(): ?FinanceReport
    {
        return FinanceReport::fromInput([
            'kind' => $this->kind, 'unit' => $this->unit, 'period' => $this->period,
            'month' => $this->month, 'from' => $this->from, 'to' => $this->to, 'year' => $this->year,
        ]);
    }

    public function exportExcel()
    {
        abort_unless(auth()->user()?->can(FinanceAccess::REPORTS), 403);

        $report = $this->report();
        abort_if($report === null, 404);

        return Excel::download(new FinanceReportExport($report), 'finance-report-'.now()->format('Ymd-His').'.xlsx');
    }

    public function render()
    {
        $report = $this->report();

        return view('livewire.finance.reports', [
            'report'   => $report,
            'data'     => $report ? ($report->kind === 'unit' ? $report->unitReport() : $report->totalReport()) : null,
            'items'    => $report?->items() ?? [],
            'units'    => FinanceUnit::ordered()->get(),
            'months'   => array_reverse(FinanceReport::availableMonths()),
            'years'    => FinanceCalendar::fiscalYears(),
            'pdfUrl'   => $report ? route('finance.reports.pdf', $report->query()) : null,
        ])->title(__('home.fin_reports'));
    }
}
