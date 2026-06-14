<?php

namespace App\Livewire\Reports;

use App\Exports\OfficesByTypeExport;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\OfficeType;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
#[Title('تقرير المقرات حسب المحافظة والنوع')]
class OfficesByType extends Component
{
    // ── الفلاتر ──
    public array $governorateIds = [];
    public array $typeIds = [];

    /** snapshot وقت الضغط على "بحث" — العرض يقرأ منه فقط */
    public array $applied = [];
    public bool $hasSearched = false;

    protected array $filterKeys = ['governorateIds', 'typeIds'];

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
        ];
        $this->hasSearched = true;
    }

    public function resetFilters(): void
    {
        $this->reset($this->filterKeys);
        $this->applied     = [];
        $this->hasSearched = false;
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
     * يبني المصفوفة المتقاطعة: صفوف = محافظات، أعمدة = أنواع، خلايا = عدد المقرات.
     *
     * @return array{governorates:\Illuminate\Support\Collection, types:\Illuminate\Support\Collection, map:array}
     */
    public function buildMatrix(?array $allowedGovIds): array
    {
        $f = $this->applied;

        // صفوف: المحافظات (مقيّدة بالنطاق + المختارة)
        $governorates = Governorate::query()
            ->when($allowedGovIds, fn ($q) => $q->whereIn('id', $allowedGovIds))
            ->when($f['governorateIds'] ?? [], fn ($q) => $q->whereIn('id', $f['governorateIds']))
            ->orderBy('order')->orderBy('id')
            ->get(['id', 'name']);

        // أعمدة: أنواع المقرات (المختارة أو الكل)
        $types = OfficeType::query()
            ->when($f['typeIds'] ?? [], fn ($q) => $q->whereIn('id', $f['typeIds']))
            ->orderBy('name')
            ->get(['id', 'name']);

        // العدّ المجمّع لكل (محافظة، نوع)
        $counts = Office::query()
            ->when($allowedGovIds, fn ($q) => $q->whereIn('governorate_id', $allowedGovIds))
            ->when($f['governorateIds'] ?? [], fn ($q) => $q->whereIn('governorate_id', $f['governorateIds']))
            ->when($f['typeIds'] ?? [], fn ($q) => $q->whereIn('type_id', $f['typeIds']))
            ->selectRaw('governorate_id, type_id, COUNT(*) as cnt')
            ->groupBy('governorate_id', 'type_id')
            ->get();

        $map = [];
        foreach ($counts as $c) {
            $map[$c->governorate_id][$c->type_id] = (int) $c->cnt;
        }

        return compact('governorates', 'types', 'map');
    }

    public function exportExcel()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        $m = $this->buildMatrix($this->allowedGovIds());

        return Excel::download(
            new OfficesByTypeExport($m['governorates'], $m['types'], $m['map']),
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
            : ['governorates' => collect(), 'types' => collect(), 'map' => []];

        return view('livewire.reports.offices-by-type', [
            'matrix'        => $matrix,
            'governorates'  => $governorates,
            'officeTypes'   => OfficeType::orderBy('name')->get(),
        ]);
    }
}
