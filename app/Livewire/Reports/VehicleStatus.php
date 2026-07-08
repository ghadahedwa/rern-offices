<?php

namespace App\Livewire\Reports;

use App\Exports\VehicleStatusExport;
use App\Models\Governorate;
use App\Models\Vehicle;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
#[Title('تقرير الحالة التشغيلية للسيارات')]
class VehicleStatus extends Component
{
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
     * يبني المصفوفة: صفوف = محافظات، أعمدة = الحالات الثلاث + الإجمالي.
     * الخلايا = عدد السيارات لكل (محافظة، حالة).
     *
     * @return array{governorates:\Illuminate\Support\Collection, counts:array}
     */
    public function buildMatrix(?array $allowedGovIds): array
    {
        $govIds = $this->applied['governorateIds'] ?? [];

        $governorates = Governorate::query()
            ->when($allowedGovIds, fn ($q) => $q->whereIn('id', $allowedGovIds))
            ->when($govIds, fn ($q) => $q->whereIn('id', $govIds))
            ->orderBy('order')->orderBy('id')
            ->get(['id', 'name']);

        $rows = Vehicle::query()
            ->when($allowedGovIds, fn ($q) => $q->whereIn('governorate_id', $allowedGovIds))
            ->when($govIds, fn ($q) => $q->whereIn('governorate_id', $govIds))
            ->selectRaw('governorate_id, status, COUNT(*) as cnt')
            ->groupBy('governorate_id', 'status')
            ->get();

        $counts = [];
        foreach ($rows as $r) {
            $counts[$r->governorate_id][$r->status] = (int) $r->cnt;
        }

        return compact('governorates', 'counts');
    }

    public function exportExcel()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        $m = $this->buildMatrix($this->allowedGovIds());

        return Excel::download(
            new VehicleStatusExport($m['governorates'], $m['counts']),
            'vehicle-status-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportPdf()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        session(['vehicle_status_filters' => $this->applied]);
        $this->js("window.open('" . route('reports.vehicle-status.pdf') . "', '_blank')");
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
            : ['governorates' => collect(), 'counts' => []];

        return view('livewire.reports.vehicle-status', [
            'matrix'       => $matrix,
            'governorates' => $governorates,
            'statuses'     => Vehicle::STATUSES,
        ]);
    }
}
