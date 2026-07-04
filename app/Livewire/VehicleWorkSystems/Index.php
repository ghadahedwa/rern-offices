<?php

namespace App\Livewire\VehicleWorkSystems;

use App\Models\VehicleWorkSystem;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('أنظمة عمل السيارات')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showDelete = false;
    public ?int $deletingId = null;
    public string $deletingLabel = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function askDelete(int $id): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
        $vehicleWorkSystem = VehicleWorkSystem::findOrFail($id);
        $this->deletingId    = $vehicleWorkSystem->id;
        $this->deletingLabel = $vehicleWorkSystem->name;
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
        if ($this->deletingId) {
            VehicleWorkSystem::findOrFail($this->deletingId)->delete();
            $this->reset('deletingId', 'deletingLabel', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.vehicle_work_system_deleted'));
        }
    }

    public function render()
    {
        return view('livewire.vehicle-work-systems.index', [
            'vehicleWorkSystems' => VehicleWorkSystem::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(15),
            'isSuperAdmin' => Auth::user()?->hasRole('super-admin'),
        ]);
    }
}
