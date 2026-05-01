<?php

namespace App\Livewire\Offices;

use App\Models\Governorate;
use App\Models\Office;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('إضافة مقر')]
class Create extends Component
{
    public int $step = 1;
    public int $totalSteps = 4;

    // Step 1 — Basic Info
    #[Url]
    public ?int $governorate_id = null;
    public ?int $parent_office_id = null;
    public string $name = '';
    public string $established_at = '';
    public string $type = '';
    public string $location_description = '';
    public string $work_system = '';
    public string $address = '';
    public string $google_maps_link = '';
    public string $floors_description = '';
    public string $connection_type = '';

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->hasRole('super-admin') || auth()->user()?->can('offices.create'),
            403
        );

    }

public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'governorate_id' => 'required|exists:governorates,id',
                'name'           => 'required|string|max:255',
                'type'           => 'required|string',
                'work_system'    => 'required|string',
            ], [
                'governorate_id.required' => 'يرجى اختيار المحافظة',
                'name.required'           => 'يرجى إدخال اسم المقر',
                'type.required'           => 'يرجى اختيار نوع المقر',
                'work_system.required'    => 'يرجى اختيار نظام العمل',
            ]);
        }

        $this->step++;
    }

    public function prevStep(): void
    {
        $this->step--;
    }

    public function save(): void
    {
        $this->validate([
            'governorate_id' => 'required|exists:governorates,id',
            'name'           => 'required|string|max:255',
            'type'           => 'required|string',
            'work_system'    => 'required|string',
        ]);

        Office::create([
            'governorate_id'       => $this->governorate_id,
            'parent_office_id'     => $this->parent_office_id ?: null,
            'name'                 => $this->name,
            'established_at'       => $this->established_at ?: null,
            'type'                 => $this->type,
            'location_description' => $this->location_description ?: null,
            'work_system'          => $this->work_system,
            'address'              => $this->address ?: null,
            'google_maps_link'     => $this->google_maps_link ?: null,
            'floors_description'   => $this->floors_description ?: null,
            'connection_type'      => $this->connection_type ?: null,
        ]);

        Flux::toast(variant: 'success', text: __('home.office_created'));
        $this->redirect(route('offices.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.offices.create', [
            'governorates' => Governorate::orderBy('id')->get(),
            'mainOffices'  => Office::where('type', 'main')->orderBy('name')->get(),
            'types'        => Office::TYPES,
            'locations'    => Office::LOCATION_DESCRIPTIONS,
            'workSystems'  => Office::WORK_SYSTEMS,
            'connections'  => Office::CONNECTION_TYPES,
        ]);
    }
}
