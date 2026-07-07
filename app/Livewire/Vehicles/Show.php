<?php

namespace App\Livewire\Vehicles;

use App\Models\StatType;
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
        $labels = [
            1 => 'vehicle_stat_transactions',
            2 => 'vehicle_stat_form_sales',
            3 => 'vehicle_stat_folder_sales',
        ];

        $statTypes = StatType::whereIn('group_key', ['transactions', 'forms_folders'])
            ->orderBy('group_key')
            ->orderBy('order')
            ->get();

        $statistics = $this->vehicle->statistics()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->groupBy('stat_type_id');

        return view('livewire.vehicles.show', [
            'statTypes'    => $statTypes,
            'statLabels'   => $labels,
            'statistics'   => $statistics,
        ]);
    }
}
