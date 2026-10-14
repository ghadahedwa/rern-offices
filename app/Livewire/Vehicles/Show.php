<?php

namespace App\Livewire\Vehicles;

use App\Livewire\Vehicles\StatTab\Documentation;
use App\Models\Vehicle;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('تفاصيل السيارة')]
class Show extends Component
{
    public Vehicle $vehicle;
    public string $activeTab = 'basic';
    public bool $canEdit = false;

    public function mount(Vehicle $vehicle): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('vehicles.view') || $user?->can('vehicles.edit'),
            403
        );

        $this->canEdit = $user?->hasRole('super-admin') || $user?->can('vehicles.edit');

        activity()
            ->performedOn($vehicle)
            ->causedBy($user)
            ->event('viewed')
            ->log('عرض سيارة');

        $this->vehicle = $vehicle->load([
            'governorate',
            'type',
            'workSystem',
            'workingHour',
            'brand',
            'locations',
            'brokenDevices.deviceType',
            'media',
        ]);
    }

    public function render()
    {
        // الأقسام والتسميات والترتيب من تاب الإدخال نفسه — العرض لا يخالف ما يُدخَل
        $statistics = $this->vehicle->statistics()
            ->orderByDesc('year')
            ->orderByRaw('month IS NULL')
            ->orderByDesc('month')
            ->get()
            ->groupBy('stat_type_id');

        return view('livewire.vehicles.show', [
            'statTypes'  => Documentation::orderedTypes(),
            'statistics' => $statistics,
            'yearTotals' => Documentation::transactionYearTotals($this->vehicle),
        ]);
    }
}
