<?php

namespace App\Livewire\Vehicles;

use App\Models\Governorate;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Models\VehicleWorkSystem;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('السيارات المتنقلة')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    #[Url]
    public ?int $governorate_id = null;

    #[Url]
    public ?int $type_id = null;

    #[Url]
    public ?int $work_system_id = null;

    #[Url]
    public string $status = '';

    public bool $showDelete    = false;
    public ?int  $deletingId   = null;
    public string $deletingLabel = '';

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->hasRole('super-admin') || auth()->user()?->can('vehicles.index'),
            403
        );
    }

    public function updatingSearch(): void        { $this->resetPage(); }
    public function updatingGovernorateId(): void { $this->resetPage(); }
    public function updatingTypeId(): void        { $this->resetPage(); }
    public function updatingWorkSystemId(): void  { $this->resetPage(); }
    public function updatingStatus(): void        { $this->resetPage(); }

    public function askDelete(int $id): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin') || auth()->user()?->can('vehicles.delete'), 403);
        $vehicle = Vehicle::findOrFail($id);
        $this->deletingId    = $vehicle->id;
        $this->deletingLabel = $vehicle->name;
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin') || auth()->user()?->can('vehicles.delete'), 403);
        if ($this->deletingId) {
            Vehicle::findOrFail($this->deletingId)->delete();
            $this->reset('deletingId', 'deletingLabel', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.vehicle_deleted'));
        }
    }

    public function render()
    {
        $user         = auth()->user();
        $isSuperAdmin = $user?->hasRole('super-admin');

        $governorates  = $isSuperAdmin
            ? Governorate::orderBy('order')->orderBy('id')->get()
            : $user->governorates()->orderBy('order')->orderBy('id')->get();

        $allowedGovIds = $isSuperAdmin ? null : $governorates->pluck('id');

        $vehicles = Vehicle::with(['governorate', 'type', 'workSystem'])
            ->when($allowedGovIds, fn($q) => $q->whereIn('governorate_id', $allowedGovIds))
            ->when($this->governorate_id, fn($q) => $q->where('governorate_id', $this->governorate_id))
            ->when($this->type_id, fn($q) => $q->where('type_id', $this->type_id))
            ->when($this->work_system_id, fn($q) => $q->where('work_system_id', $this->work_system_id))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(15);

        return view('livewire.vehicles.index', [
            'vehicles'     => $vehicles,
            'governorates' => $governorates,
            'types'        => VehicleType::orderBy('name')->get(),
            'workSystems'  => VehicleWorkSystem::orderBy('name')->get(),
            'canCreate'    => $isSuperAdmin || $user?->can('vehicles.create'),
            'canEdit'      => $isSuperAdmin || $user?->can('vehicles.edit'),
            'canDelete'    => $isSuperAdmin || $user?->can('vehicles.delete'),
        ]);
    }
}
