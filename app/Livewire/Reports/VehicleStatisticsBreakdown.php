<?php

namespace App\Livewire\Reports;

use App\Exports\VehicleStatisticsBreakdownExport;
use App\Models\Governorate;
use App\Models\Vehicle;
use App\Reports\VehicleStatistics as YearReport;
use App\Reports\VehicleStatisticsBreakdown as Report;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
#[Title('إحصائيات السيارات التفصيلية')]
class VehicleStatisticsBreakdown extends Component
{
    // ── الفلاتر ── (تُفحص كلها في `Report::make()` كأنها من العميل)
    public array $governorateIds = [];
    public string $metric = 'tx';
    public string $level = 'governorate';
    public string $period = 'yearly';
    public string $year = '';
    /** سيارةٌ واحدة → الشكل الرأسي (الفترات صفوف) */
    public string $vehicleId = '';
    /** بحثٌ داخل منسدلة المحافظات (المنسدلة نفسها التي في لوحة العاملين بالتعاقد) */
    public string $governorateSearch = '';

    public array $applied = [];
    public bool $hasSearched = false;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('super-admin') || $user?->can('vehicles.export'), 403);

        $this->year = (string) YearReport::sanitizeYear(null);
    }

    public function search(): void
    {
        $this->applied = [
            'governorateIds' => $this->governorateIds,
            'metric'         => $this->metric,
            'level'          => $this->level,
            'period'         => $this->period,
            'year'           => $this->year,
            'vehicleId'      => $this->vehicleId,
        ];
        $this->hasSearched = true;
    }

    public function resetFilters(): void
    {
        $this->reset(['governorateIds', 'metric', 'level', 'period', 'vehicleId', 'governorateSearch']);
        $this->year        = (string) YearReport::sanitizeYear(null);
        $this->applied     = [];
        $this->hasSearched = false;
        $this->dispatch('filters-reset');
    }

    /** تضييق المحافظات يُسقط سيارةً خرجت منها — وإلا ظهر تقريرٌ فارغ بلا سبب ظاهر */
    public function updatedGovernorateIds(): void
    {
        if ($this->vehicleId !== '' && ! $this->vehicleOptions()->flatten()->contains('id', (int) $this->vehicleId)) {
            $this->vehicleId = '';
        }
    }

    /** سيارات النطاق (والمحافظات المختارة) مجمّعةً بالمحافظة، بالترتيب الطبيعي للأسماء */
    protected function vehicleOptions(): \Illuminate\Support\Collection
    {
        $allowed = VehicleFridayWork::allowedGovIdsFor(auth()->user());
        $govIds  = array_filter($this->governorateIds, fn ($id) => is_scalar($id) && ctype_digit((string) $id));

        return Vehicle::query()
            ->when($allowed !== null, fn ($q) => $q->whereIn('governorate_id', $allowed))
            ->when($govIds, fn ($q) => $q->whereIn('governorate_id', $govIds))
            ->with('governorate:id,name,order')
            ->orderBy(Governorate::select('order')->whereColumn('governorates.id', 'vehicles.governorate_id'))
            ->orderBy('governorate_id')
            ->tap(fn ($q) => \App\Support\NaturalSort::orderBy($q, 'vehicles.name'))
            ->get(['id', 'governorate_id', 'name'])
            ->groupBy(fn (Vehicle $v) => $v->governorate?->name ?? '');
    }

    protected function report(): Report
    {
        return Report::make(VehicleFridayWork::allowedGovIdsFor(auth()->user()), $this->applied);
    }

    public function exportExcel()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        $report = $this->report();

        return Excel::download(
            new VehicleStatisticsBreakdownExport($report),
            'vehicle-statistics-breakdown-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportPdf()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        session(['vehicle_statistics_breakdown_filters' => $this->applied]);
        $this->js("window.open('" . route('reports.vehicle-statistics-breakdown.pdf') . "', '_blank')");
    }

    public function render()
    {
        $user = auth()->user();

        $governorates = $user?->hasRole('super-admin')
            ? Governorate::orderBy('order')->orderBy('id')->get()
            : $user->governorates()->orderBy('order')->orderBy('id')->get();

        return view('livewire.reports.vehicle-statistics-breakdown', [
            // البحث في السيرفر بـ`ArabicText`، والمختارة تبقى ظاهرةً ولو لم تطابق
            'governorateChoices' => \App\Support\Contractors\AttendanceReportQuery::filterOptions($governorates, $this->governorateSearch, $this->governorateIds),
            'years'        => YearReport::years(),
            'vehicles'     => $this->vehicleOptions(),
            'report'       => $this->hasSearched ? $this->report() : null,
        ]);
    }
}
