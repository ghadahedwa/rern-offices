<?php

namespace App\Livewire\Reports;

use App\Exports\VehicleStatisticsExport;
use App\Models\Governorate;
use App\Reports\VehicleStatistics as Report;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
#[Title('إحصائيات السيارات')]
class VehicleStatistics extends Component
{
    // دورة الضغط والرابط من الـtrait، والترتيب نفسه داخل التقرير (داخل كل محافظة، ويتبعه الـPDF والـExcel)
    use \App\Livewire\Concerns\WithTableSorting;

    protected function sortableColumns(): array
    {
        return array_fill_keys(Report::SORTABLE, null);
    }

    /** لا يُستدعى — الترتيب الافتراضي (المحافظة ثم الاسم) في التقرير نفسه */
    protected function defaultOrder(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query;
    }

    /** الفلاتر المطبَّقة + الترتيب الحالي — يُطبَّق الترتيب فوراً بلا ضغط «بحث» */
    protected function filtersWithSort(): array
    {
        return $this->applied + ['sort' => $this->sortBy, 'dir' => $this->sortDir];
    }
    // ── الفلاتر ──
    public array $governorateIds = [];
    public string $year = '';

    public array $applied = [];
    public bool $hasSearched = false;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('super-admin') || $user?->can('vehicles.export'), 403);

        $this->year = (string) Report::sanitizeYear(null);
    }

    public function search(): void
    {
        $this->applied     = ['governorateIds' => $this->governorateIds, 'year' => $this->year];
        $this->hasSearched = true;
    }

    public function resetFilters(): void
    {
        $this->reset(['governorateIds']);
        $this->year        = (string) Report::sanitizeYear(null);
        $this->applied     = [];
        $this->hasSearched = false;
        $this->dispatch('filters-reset');
    }

    protected function report(): Report
    {
        return Report::make(VehicleFridayWork::allowedGovIdsFor(auth()->user()), $this->filtersWithSort());
    }

    public function exportExcel()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        $report = $this->report();

        return Excel::download(
            new VehicleStatisticsExport($report),
            'vehicle-statistics-' . $report->year() . '-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportPdf()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        session(['vehicle_statistics_filters' => $this->filtersWithSort()]);
        $this->js("window.open('" . route('reports.vehicle-statistics.pdf') . "', '_blank')");
    }

    public function render()
    {
        $user = auth()->user();

        $governorates = $user?->hasRole('super-admin')
            ? Governorate::orderBy('order')->orderBy('id')->get()
            : $user->governorates()->orderBy('order')->orderBy('id')->get();

        return view('livewire.reports.vehicle-statistics', [
            'governorates' => $governorates,
            'years'        => Report::years(),
            'report'       => $this->hasSearched ? $this->report() : null,
        ]);
    }
}
