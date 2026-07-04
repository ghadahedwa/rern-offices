<?php

namespace App\Livewire\VehicleWorkingHours;

use App\Models\VehicleWorkingHour;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('أوقات عمل السيارات')]
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
        $vehicleWorkingHour = VehicleWorkingHour::findOrFail($id);
        $this->deletingId    = $vehicleWorkingHour->id;
        $this->deletingLabel = $vehicleWorkingHour->name;
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
        if ($this->deletingId) {
            VehicleWorkingHour::findOrFail($this->deletingId)->delete();
            $this->reset('deletingId', 'deletingLabel', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.vehicle_working_hour_deleted'));
        }
    }

    public function render()
    {
        return view('livewire.vehicle-working-hours.index', [
            'vehicleWorkingHours' => VehicleWorkingHour::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(15),
            'isSuperAdmin' => Auth::user()?->hasRole('super-admin'),
        ]);
    }
}
