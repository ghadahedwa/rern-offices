<?php

namespace App\Livewire\Reports;

use App\Exports\VehicleFridayWorkExport;
use App\Models\Governorate;
use App\Reports\VehicleFridayWork as Report;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
#[Title('السيارات العاملة أيام الجمع')]
class VehicleFridayWork extends Component
{
    // ── الفلاتر ──
    public array $governorateIds = [];
    public string $friday = '';   // '' الكل · works · not_works · unset

    public array $applied = [];
    public bool $hasSearched = false;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('super-admin') || $user?->can('vehicles.export'), 403);
    }

    public function search(): void
    {
        $this->applied     = ['governorateIds' => $this->governorateIds, 'friday' => $this->friday];
        $this->hasSearched = true;
    }

    public function resetFilters(): void
    {
        $this->reset(['governorateIds', 'friday']);
        $this->applied     = [];
        $this->hasSearched = false;
        $this->dispatch('filters-reset');
    }

    /** null = بلا حدّ (super-admin) · [] = مستخدم بلا محافظات لا يرى شيئاً */
    public static function allowedGovIdsFor($user): ?array
    {
        return $user?->hasRole('super-admin')
            ? null
            : ($user?->governorates()->pluck('governorates.id')->all() ?? []);
    }

    protected function report(): Report
    {
        return Report::make(self::allowedGovIdsFor(auth()->user()), $this->applied);
    }

    public function exportExcel()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        return Excel::download(
            new VehicleFridayWorkExport($this->report()),
            'vehicle-friday-work-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportPdf()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        session(['vehicle_friday_filters' => $this->applied]);
        $this->js("window.open('" . route('reports.vehicle-friday.pdf') . "', '_blank')");
    }

    public function render()
    {
        $user = auth()->user();

        $governorates = $user?->hasRole('super-admin')
            ? Governorate::orderBy('order')->orderBy('id')->get()
            : $user->governorates()->orderBy('order')->orderBy('id')->get();

        $report = $this->hasSearched ? $this->report() : null;

        return view('livewire.reports.vehicle-friday-work', [
            'governorates' => $governorates,
            'labels'       => Report::labels(),
            'rows'         => $report?->governorates() ?? collect(),
            'counts'       => $report?->counts() ?? [],
            'vehicles'     => $report?->vehicles() ?? collect(),
        ]);
    }
}
