<?php

namespace App\Livewire\Vehicles;

use App\Models\Governorate;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleLocation;
use App\Models\VehicleType;
use App\Models\VehicleWorkingHour;
use App\Models\VehicleWorkSystem;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('سيارة متنقلة')]
class Create extends Component
{
    public ?int $vehicle_id = null;
    public bool $isEditing  = false;

    // Basic data
    public $governorate_id     = null;
    public string $name        = '';
    public $type_id            = null;
    public $work_system_id     = null;
    public $working_hours_id   = null;
    public $brand_id           = null;
    public string $license_plate         = '';
    public string $manufacture_year      = '';
    public string $chassis_number        = '';
    public string $license_expiry_date   = '';
    public string $status                = '';
    public string $overnight_address     = '';
    public string $storage_room_location = '';
    public string $notes                 = '';

    // مواقع التمركز (صفوف ديناميكية)
    public array $locations = [];

    public function mount(?Vehicle $vehicle = null): void
    {
        $user = auth()->user();

        if ($vehicle && $vehicle->exists) {
            abort_unless($user?->hasRole('super-admin') || $user?->can('vehicles.edit'), 403);
            $this->assertVehicleInScope($vehicle);
            $this->isEditing   = true;
            $this->vehicle_id  = $vehicle->id;
            $this->loadVehicle($vehicle);
        } else {
            abort_unless($user?->hasRole('super-admin') || $user?->can('vehicles.create'), 403);
        }

        if (empty($this->locations)) {
            $this->locations = [['day' => '', 'address' => '']];
        }
    }

    public function addLocation(): void
    {
        $this->locations[] = ['day' => '', 'address' => ''];
    }

    public function removeLocation(int $index): void
    {
        array_splice($this->locations, $index, 1);
        if (empty($this->locations)) {
            $this->locations = [['day' => '', 'address' => '']];
        }
    }

    public function save(): void
    {
        $governorateRule = ['required', 'exists:governorates,id'];
        if (!auth()->user()?->hasRole('super-admin')) {
            $governorateRule[] = Rule::in($this->allowedGovernorateIds());
        }

        $this->validate([
            'governorate_id'         => $governorateRule,
            'name'                   => ['required', 'string', 'max:255'],
            'type_id'                => ['nullable', 'exists:vehicle_types,id'],
            'work_system_id'         => ['nullable', 'exists:vehicle_work_systems,id'],
            'working_hours_id'       => ['nullable', 'exists:vehicle_working_hours,id'],
            'brand_id'               => ['nullable', 'exists:vehicle_brands,id'],
            'license_plate'          => ['nullable', 'string', 'max:50'],
            'manufacture_year'       => ['nullable', 'integer', 'min:1980', 'max:' . (date('Y') + 1)],
            'chassis_number'         => ['nullable', 'string', 'max:100'],
            'license_expiry_date'    => ['nullable', 'date'],
            'status'                 => ['nullable', Rule::in(array_keys(Vehicle::STATUSES))],
            'overnight_address'      => ['nullable', 'string', 'max:500'],
            'storage_room_location'  => ['nullable', 'string', 'max:500'],
            'notes'                  => ['nullable', 'string'],
            'locations.*.day'        => ['nullable', Rule::in(array_keys(VehicleLocation::DAYS))],
            'locations.*.address'    => ['nullable', 'string', 'max:500'],
        ], [
            'governorate_id.required' => 'يرجى اختيار المحافظة',
            'governorate_id.in'       => 'لا يمكنك حفظ سيارة في محافظة خارج نطاقك',
            'name.required'           => 'يرجى إدخال اسم السيارة',
        ]);

        $data = [
            'governorate_id'        => $this->governorate_id ?: null,
            'name'                  => $this->name,
            'type_id'               => $this->type_id ?: null,
            'work_system_id'        => $this->work_system_id ?: null,
            'working_hours_id'      => $this->working_hours_id ?: null,
            'brand_id'              => $this->brand_id ?: null,
            'license_plate'         => $this->license_plate ?: null,
            'manufacture_year'      => $this->manufacture_year ?: null,
            'chassis_number'        => $this->chassis_number ?: null,
            'license_expiry_date'   => $this->license_expiry_date ?: null,
            'status'                => $this->status ?: null,
            'overnight_address'     => $this->overnight_address ?: null,
            'storage_room_location' => $this->storage_room_location ?: null,
            'notes'                 => $this->notes ?: null,
        ];

        if ($this->isEditing) {
            $vehicle = Vehicle::findOrFail($this->vehicle_id);
            $vehicle->update($data);
            $message = __('home.vehicle_updated');
        } else {
            $vehicle = Vehicle::create($data);
            $this->vehicle_id = $vehicle->id;
            $this->isEditing  = true;
            $message = __('home.vehicle_created');
        }

        // حفظ مواقع التمركز
        $vehicle->locations()->delete();
        foreach ($this->locations as $loc) {
            if (!empty($loc['day']) && !empty($loc['address'])) {
                $vehicle->locations()->create($loc);
            }
        }

        Flux::toast(variant: 'success', text: $message);
        $this->redirect(route('vehicles.edit', $vehicle), navigate: true);
    }

    private function loadVehicle(Vehicle $vehicle): void
    {
        $this->governorate_id        = $vehicle->governorate_id;
        $this->name                  = $vehicle->name;
        $this->type_id               = $vehicle->type_id;
        $this->work_system_id        = $vehicle->work_system_id;
        $this->working_hours_id      = $vehicle->working_hours_id;
        $this->brand_id              = $vehicle->brand_id;
        $this->license_plate         = $vehicle->license_plate ?? '';
        $this->manufacture_year      = (string) ($vehicle->manufacture_year ?? '');
        $this->chassis_number        = $vehicle->chassis_number ?? '';
        $this->license_expiry_date   = $vehicle->license_expiry_date?->format('Y-m-d') ?? '';
        $this->status                = $vehicle->status ?? '';
        $this->overnight_address     = $vehicle->overnight_address ?? '';
        $this->storage_room_location = $vehicle->storage_room_location ?? '';
        $this->notes                 = $vehicle->notes ?? '';

        $this->locations = $vehicle->locations
            ->map(fn($l) => ['day' => $l->day, 'address' => $l->address])
            ->toArray();
    }

    private function assertVehicleInScope(Vehicle $vehicle): void
    {
        if (auth()->user()?->hasRole('super-admin')) return;
        abort_unless(in_array($vehicle->governorate_id, $this->allowedGovernorateIds(), true), 403);
    }

    private function allowedGovernorateIds(): array
    {
        return auth()->user()?->governorates()->pluck('id')->all() ?? [];
    }

    public function render()
    {
        $user = auth()->user();
        return view('livewire.vehicles.create', [
            'governorates' => $user?->hasRole('super-admin')
                ? Governorate::orderBy('order')->orderBy('id')->get()
                : $user->governorates()->orderBy('order')->orderBy('id')->get(),
            'types'        => VehicleType::orderBy('name')->get(),
            'brands'       => VehicleBrand::orderBy('name')->get(),
            'workSystems'  => VehicleWorkSystem::orderBy('name')->get(),
            'workingHours' => VehicleWorkingHour::orderBy('name')->get(),
        ]);
    }
}
