<?php

namespace App\Livewire\Reports;

use App\Exports\DeviceCountExport;
use App\Models\DeviceType;
use App\Models\Governorate;
use App\Reports\DeviceCountMatrix;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
#[Title('بيان الأجهزة العددي')]
class DeviceCount extends Component
{
    /** الأجهزة الشغالة الثمانية: عمود قاعدة البيانات => التسمية */
    public const DEVICES = [
        'computers_count'        => 'كمبيوتر',
        'monitors_count'         => 'شاشات العرض',
        'printers_count'         => 'طابعات',
        'scanners_count'         => 'ماسحات',
        'fingerprints_count'     => 'بصمة',
        'payment_machine_count'  => 'ماكينات دفع',
        'air_conditioners_count' => 'مكيفات',
        'ups_count'              => 'UPS',
    ];

    // ── الفلاتر ──
    public array $governorateIds = [];
    public array $workingDevices = [];   // مفاتيح الأجهزة الشغالة المختارة (فارغ = الكل)
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
            $user?->hasRole('super-admin') || $user?->can('offices.export'),
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

        $m = DeviceCountMatrix::build($this->allowedGovIds(), $this->applied);

        return Excel::download(
            new DeviceCountExport($m['governorates'], $m['workingCols'], $m['brokenTypes'], $m['sums'], $m['brokenSums']),
            'device-count-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function exportPdf()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        session(['device_count_filters' => $this->applied]);
        $this->js("window.open('" . route('reports.device-count.pdf') . "', '_blank')");
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
            ? DeviceCountMatrix::build($allowedGovIds, $this->applied)
            : ['governorates' => collect(), 'workingCols' => [], 'brokenTypes' => collect(), 'sums' => [], 'brokenSums' => []];

        return view('livewire.reports.device-count', [
            'matrix'          => $matrix,
            'governorates'    => $governorates,
            'deviceOptions'   => self::DEVICES,
            'brokenTypeOptions' => DeviceType::orderBy('name')->get(),
        ]);
    }
}
