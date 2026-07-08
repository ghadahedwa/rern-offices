<?php

namespace App\Livewire\Reports;

use App\Exports\VehicleLicensesExport;
use App\Models\Governorate;
use App\Models\Vehicle;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
#[Title('تقرير تراخيص السيارات')]
class VehicleLicenses extends Component
{
    // ── الفلاتر ──
    public array $governorateIds = [];
    /** نطاق التنبيه: عرض ما ينتهي خلال X يوم (بالإضافة للمنتهية) — فارغ = كل السيارات */
    public ?int $withinDays = 60;

    public array $applied = [];
    public bool $hasSearched = false;

    protected array $filterKeys = ['governorateIds', 'withinDays'];

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
        $this->applied = [
            'governorateIds' => $this->governorateIds,
            'withinDays'     => $this->withinDays !== null && $this->withinDays !== '' ? (int) $this->withinDays : null,
        ];
        $this->hasSearched = true;
    }

    public function resetFilters(): void
    {
        $this->reset($this->filterKeys);
        $this->withinDays  = 60;
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

    /** عتبة "تنتهي قريباً": نطاق الفلتر المطبَّق إن وُجد، وإلا 30 يوماً (تُستدعى من الـ view أيضاً) */
    public function soonDays(): int
    {
        return $this->applied['withinDays'] ?? 30;
    }

    /**
     * قائمة السيارات مرتّبة بتاريخ انتهاء الترخيص (تصاعدياً، الأقرب/المنتهي أولاً).
     * السيارات بلا تاريخ ترخيص تظهر في النهاية.
     * فلتر withinDays: يعرض فقط ما انتهى أو ينتهي خلال X يوم (السيارات بلا تاريخ تُستبعد حينها).
     */
    public function buildRows(?array $allowedGovIds)
    {
        $govIds     = $this->applied['governorateIds'] ?? [];
        $withinDays = $this->applied['withinDays'] ?? null;

        return Vehicle::query()
            ->with('governorate:id,name')
            ->when($allowedGovIds, fn ($q) => $q->whereIn('governorate_id', $allowedGovIds))
            ->when($govIds, fn ($q) => $q->whereIn('governorate_id', $govIds))
            ->when($withinDays !== null, fn ($q) => $q
                ->whereNotNull('license_expiry_date')
                ->whereDate('license_expiry_date', '<=', now()->addDays($withinDays)->toDateString())
            )
            ->orderByRaw('license_expiry_date IS NULL, license_expiry_date ASC')
            ->orderBy('name')
            ->get(['id', 'governorate_id', 'name', 'license_plate', 'license_expiry_date']);
    }

    public function exportExcel()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        return Excel::download(
            new VehicleLicensesExport($this->buildRows($this->allowedGovIds()), $this->soonDays()),
            'vehicle-licenses-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportPdf()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        session(['vehicle_licenses_filters' => $this->applied]);
        $this->js("window.open('" . route('reports.vehicle-licenses.pdf') . "', '_blank')");
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

        return view('livewire.reports.vehicle-licenses', [
            'rows'         => $rows,
            'governorates' => $governorates,
        ]);
    }
}
