<?php

namespace App\Livewire\Offices;

use App\Models\Office;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('تفاصيل المقر')]
class Show extends Component
{
    public Office $office;
    public string $activeTab = 'basic';
    public bool $canEdit = false;

    public function mount(Office $office): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('offices.view') || $user?->can('offices.edit'),
            403
        );

        $this->canEdit = $user?->hasRole('super-admin') || $user?->can('offices.edit');

        $this->office = $office->load([
            'governorate',
            'officeType',
            'locationDescription',
            'workSystem',
            'workingHour',
            'connectionType',
            'contractualStatus',
            'structuralCondition',
            'MicrofilmOption',
            'DisabilitieAccess',
            'FireSafety',
            'DocumentPhotocopyingService',
            'BuffetService',
            'CleanlinessContract',
            'brokenDevices.deviceType',
            'media',
        ]);
    }

    public function render()
    {
        return view('livewire.offices.show');
    }
}
