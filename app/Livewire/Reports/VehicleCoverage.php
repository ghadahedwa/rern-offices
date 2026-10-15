<?php

namespace App\Livewire\Reports;

use App\Exports\VehicleCoverageExport;
use App\Models\Governorate;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
#[Title('تقرير التغطية الجغرافية للسيارات')]
class VehicleCoverage extends Component
{
    // دورة الضغط والرابط من الـtrait، والترتيب نفسه في rowsFor()
    use \App\Livewire\Concerns\WithTableSorting;

    // ── الفلاتر ──
    public array $governorateIds = [];

    public array $applied = [];
    public bool $hasSearched = false;

    protected array $filterKeys = ['governorateIds'];

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('vehicles.export'),
            403
        );
    }

    public function search(): void
    {
        $this->applied     = ['governorateIds' => $this->governorateIds];
        $this->hasSearched = true;
    }

    public function resetFilters(): void
    {
        $this->reset($this->filterKeys);
        $this->applied     = [];
        $this->hasSearched = false;
        $this->dispatch('filters-reset');
    }

    protected function allowedGovIds(): ?array
    {
        $user = auth()->user();

        return $user?->hasRole('super-admin')
            ? null
            : $user->governorates()->pluck('governorates.id')->all();
    }

    /**
     * جدول التمركز: صفوف = سيارات (مع محافظتها)، أعمدة = أيام الأسبوع.
     * الخلية = عناوين تمركز السيارة في اليوم (أو —).
     *
     * @return \Illuminate\Support\Collection  كل عنصر: {vehicle, governorate_name, days: [day => "عنوان | عنوان"]}
     */
    public function buildRows(?array $allowedGovIds)
    {
        return self::rowsFor($allowedGovIds, $this->applied + ['sort' => $this->sortBy, 'dir' => $this->sortDir]);
    }

    /** أعمدة الترتيب (قائمة بيضاء) — الافتراضي المحافظة بترتيبها ثم الاسم */
    public static function sortMap(): array
    {
        return [
            'governorate' => \App\Support\TableSort::related('governorates', 'order', 'vehicles', 'governorate_id'),
            'name'        => 'vehicles.name',
        ];
    }

    protected function sortableColumns(): array
    {
        return self::sortMap();
    }

    /** لا يُستدعى — الترتيب الافتراضي في rowsFor() */
    protected function defaultOrder(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query;
    }

    /**
     * صفوف التقرير — **المصدر الواحد للشاشة والـExcel والـPDF** (الـPDF كان يكرّر الاستعلام بنفسه).
     *
     * @param ?array $allowedGovIds null = بلا حدّ · [] = لا شيء
     */
    public static function rowsFor(?array $allowedGovIds, array $filters)
    {
        $govIds = $filters['governorateIds'] ?? [];

        $query = Vehicle::query()
            ->with(['governorate:id,name', 'locations:id,vehicle_id,day,address'])
            ->when($allowedGovIds !== null, fn ($q) => $q->whereIn('governorate_id', $allowedGovIds))
            ->when($govIds, fn ($q) => $q->whereIn('governorate_id', $govIds));

        $vehicles = \App\Support\TableSort::apply(
            $query, (string) ($filters['sort'] ?? ''), (string) ($filters['dir'] ?? 'asc'), self::sortMap(),
            fn ($q) => $q
                ->orderBy(Governorate::select('order')->whereColumn('governorates.id', 'vehicles.governorate_id'))
                ->orderBy('governorate_id')
                ->orderBy('name'),
            'vehicles.id', 'asc',
        )->get();

        return $vehicles->map(function (Vehicle $v) {
            $days = [];
            foreach (array_keys(VehicleLocation::DAYS) as $day) {
                $addresses = $v->locations
                    ->where('day', $day)
                    ->pluck('address')
                    ->filter()
                    ->implode(' | ');
                $days[$day] = $addresses !== '' ? $addresses : null;
            }

            return [
                'name'             => $v->name,
                'governorate_name' => $v->governorate->name ?? '—',
                'days'             => $days,
            ];
        });
    }

    public function exportExcel()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        return Excel::download(
            new VehicleCoverageExport($this->buildRows($this->allowedGovIds())),
            'vehicle-coverage-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportPdf()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        session(['vehicle_coverage_filters' => $this->applied + ['sort' => $this->sortBy, 'dir' => $this->sortDir]]);
        $this->js("window.open('" . route('reports.vehicle-coverage.pdf') . "', '_blank')");
    }

    public function render()
    {
        $user         = auth()->user();
        $isSuperAdmin = $user?->hasRole('super-admin');

        $governorates = $isSuperAdmin
            ? Governorate::orderBy('order')->orderBy('id')->get()
            : $user->governorates()->orderBy('order')->orderBy('id')->get();

        $allowedGovIds = $isSuperAdmin ? null : $governorates->pluck('id')->all();

        $rows = $this->hasSearched
            ? $this->buildRows($allowedGovIds)
            : collect();

        return view('livewire.reports.vehicle-coverage', [
            'rows'         => $rows,
            'governorates' => $governorates,
            'days'         => VehicleLocation::DAYS,
        ]);
    }
}
