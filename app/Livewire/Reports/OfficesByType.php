<?php

namespace App\Livewire\Reports;

use App\Exports\OfficesByTypeExport;
use App\Models\Governorate;
use App\Models\LocationDescription;
use App\Models\Office;
use App\Models\OfficeType;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
#[Title('تقرير المقرات حسب المحافظة والنوع والوصف')]
class OfficesByType extends Component
{
    // ── الفلاتر ──
    public array $governorateIds = [];
    public array $typeIds = [];
    public array $locationIds = [];

    // ── إظهار مجموعات الأعمدة ──
    public bool $showTypes = true;
    public bool $showLocations = true;

    /** snapshot وقت الضغط على "بحث" — العرض يقرأ منه فقط */
    public array $applied = [];
    public bool $hasSearched = false;

    protected array $filterKeys = ['governorateIds', 'typeIds', 'locationIds'];

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('offices.export'),
            403
        );
    }

    public function search(): void
    {
        $this->applied = [
            'governorateIds' => $this->governorateIds,
            'typeIds'        => $this->typeIds,
            'locationIds'    => $this->locationIds,
            'showTypes'      => $this->showTypes,
            'showLocations'  => $this->showLocations,
        ];
        $this->hasSearched = true;
    }

    public function resetFilters(): void
    {
        $this->reset($this->filterKeys);
        $this->showTypes     = true;
        $this->showLocations = true;
        $this->applied       = [];
        $this->hasSearched   = false;
        $this->dispatch('filters-reset');
    }

    /** المحافظات المسموح بها (null = super-admin يرى الكل) */
    protected function allowedGovIds(): ?array
    {
        $user = auth()->user();

        return $user?->hasRole('super-admin')
            ? null
            : $user->governorates()->pluck('governorates.id')->all();
    }

    /**
     * يبني المصفوفة: صفوف = محافظات، مجموعتا أعمدة (نوع + وصف الموقع) + إجمالي مقرات المحافظة.
     * الفلاتر تختار أي أعمدة تظهر؛ المحافظة تختار الصفوف. الخلايا = عدد المقرات.
     *
     * @return array{governorates:\Illuminate\Support\Collection, types:\Illuminate\Support\Collection, locations:\Illuminate\Support\Collection, typeCounts:array, locationCounts:array}
     */
    public function buildMatrix(?array $allowedGovIds): array
    {
        $f      = $this->applied;
        $govIds = $f['governorateIds'] ?? [];

        $governorates = Governorate::query()
            ->when($allowedGovIds, fn ($q) => $q->whereIn('id', $allowedGovIds))
            ->when($govIds, fn ($q) => $q->whereIn('id', $govIds))
            ->orderBy('order')->orderBy('id')
            ->get(['id', 'name']);

        $showTypes     = $f['showTypes'] ?? true;
        $showLocations = $f['showLocations'] ?? true;

        // أعمدة النوع (المختارة أو الكل) — فقط لو مفعّل عرض النوع
        $types = $showTypes
            ? OfficeType::query()
                ->when($f['typeIds'] ?? [], fn ($q) => $q->whereIn('id', $f['typeIds']))
                ->orderBy('name')->get(['id', 'name'])
            : collect();

        // أعمدة وصف الموقع (المختارة أو الكل) — فقط لو مفعّل عرض الوصف
        $locations = $showLocations
            ? LocationDescription::query()
                ->when($f['locationIds'] ?? [], fn ($q) => $q->whereIn('id', $f['locationIds']))
                ->orderBy('name')->get(['id', 'name'])
            : collect();

        // عدّ حسب النوع
        $typeCounts = [];
        if ($types->isNotEmpty()) {
            $typeRows = Office::query()
                ->when($allowedGovIds, fn ($q) => $q->whereIn('governorate_id', $allowedGovIds))
                ->when($govIds, fn ($q) => $q->whereIn('governorate_id', $govIds))
                ->selectRaw('governorate_id, type_id, COUNT(*) as cnt')
                ->groupBy('governorate_id', 'type_id')
                ->get();
            foreach ($typeRows as $r) {
                $typeCounts[$r->governorate_id][$r->type_id] = (int) $r->cnt;
            }
        }

        // عدّ حسب وصف الموقع
        $locationCounts = [];
        if ($locations->isNotEmpty()) {
            $locRows = Office::query()
                ->when($allowedGovIds, fn ($q) => $q->whereIn('governorate_id', $allowedGovIds))
                ->when($govIds, fn ($q) => $q->whereIn('governorate_id', $govIds))
                ->selectRaw('governorate_id, location_description_id, COUNT(*) as cnt')
                ->groupBy('governorate_id', 'location_description_id')
                ->get();
            foreach ($locRows as $r) {
                $locationCounts[$r->governorate_id][$r->location_description_id] = (int) $r->cnt;
            }
        }

        return compact('governorates', 'types', 'locations', 'typeCounts', 'locationCounts');
    }

    public function exportExcel()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        $m = $this->buildMatrix($this->allowedGovIds());

        return Excel::download(
            new OfficesByTypeExport($m['governorates'], $m['types'], $m['locations'], $m['typeCounts'], $m['locationCounts']),
            'offices-by-type-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportPdf()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        session(['offices_by_type_filters' => $this->applied]);
        $this->js("window.open('" . route('reports.offices-by-type.pdf') . "', '_blank')");
    }

    public function render()
    {
        $user         = auth()->user();
        $isSuperAdmin = $user?->hasRole('super-admin');

        $governorates = $isSuperAdmin
            ? Governorate::orderBy('order')->orderBy('id')->get()
            : $user->governorates()->orderBy('order')->orderBy('id')->get();

        $allowedGovIds = $isSuperAdmin ? null : $governorates->pluck('id')->all();

        $matrix = $this->hasSearched
            ? $this->buildMatrix($allowedGovIds)
            : ['governorates' => collect(), 'types' => collect(), 'locations' => collect(), 'typeCounts' => [], 'locationCounts' => []];

        return view('livewire.reports.offices-by-type', [
            'matrix'       => $matrix,
            'governorates' => $governorates,
            'officeTypes'  => OfficeType::orderBy('name')->get(),
            'locations'    => LocationDescription::orderBy('name')->get(),
        ]);
    }
}
