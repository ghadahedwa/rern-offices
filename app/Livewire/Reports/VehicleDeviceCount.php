<?php

namespace App\Livewire\Reports;

use App\Exports\VehicleDeviceCountExport;
use App\Models\Governorate;
use App\Models\VehicleDeviceType;
use App\Reports\VehicleDeviceCountMatrix;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
#[Title('بيان تجهيزات السيارات العددي')]
class VehicleDeviceCount extends Component
{
    /** تجهيزات السيارة العاملة الخمسة: عمود قاعدة البيانات => التسمية */
    public const DEVICES = [
        'laptops_count'             => 'لابتوب',
        'fingerprints_count'        => 'بصمة',
        'printers_count'            => 'طابعات',
        'collection_machines_count' => 'ماكينات تحصيل',
        'mifi_count'                => 'MiFi',
    ];

    // ── الفلاتر ──
    public array $governorateIds = [];
    public array $workingDevices = [];   // مفاتيح التجهيزات العاملة المختارة (فارغ = الكل)
    public array $brokenTypeIds  = [];    // أنواع المعطلة المختارة (فارغ = الكل)

    // ── إظهار مجموعات الأعمدة ──
    public bool $showWorking = true;
    public bool $showBroken  = true;

    public array $applied = [];
    public bool $hasSearched = false;

    protected array $filterKeys = ['governorateIds', 'workingDevices', 'brokenTypeIds'];

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
            'workingDevices' => $this->workingDevices,
            'brokenTypeIds'  => $this->brokenTypeIds,
            'showWorking'    => $this->showWorking,
            'showBroken'     => $this->showBroken,
        ];
        $this->hasSearched = true;
    }

    public function resetFilters(): void
    {
        $this->reset($this->filterKeys);
        $this->showWorking = true;
        $this->showBroken  = true;
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

    public function exportExcel()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        $m = VehicleDeviceCountMatrix::build($this->allowedGovIds(), $this->applied);

        return Excel::download(
            new VehicleDeviceCountExport($m['governorates'], $m['workingCols'], $m['brokenTypes'], $m['sums'], $m['brokenSums']),
            'vehicle-device-count-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportPdf()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        session(['vehicle_device_count_filters' => $this->applied]);
        $this->js("window.open('" . route('reports.vehicle-device-count.pdf') . "', '_blank')");
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
            ? VehicleDeviceCountMatrix::build($allowedGovIds, $this->applied)
            : ['governorates' => collect(), 'workingCols' => [], 'brokenTypes' => collect(), 'sums' => [], 'brokenSums' => []];

        return view('livewire.reports.vehicle-device-count', [
            'matrix'            => $matrix,
            'governorates'      => $governorates,
            'deviceOptions'     => self::DEVICES,
            'brokenTypeOptions' => VehicleDeviceType::orderBy('name')->get(),
        ]);
    }
}
