<?php

namespace App\Livewire\DeviceTypes;

use App\Models\DeviceType;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('أنواع الأجهزة')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showDelete = false;
    public ?int $deletingId = null;
    public string $deletingLabel = '';
    public string $deletingWarning = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function askDelete(int $id): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
        $deviceType = DeviceType::withCount('brokenDevices')->findOrFail($id);
        $this->deletingId    = $deviceType->id;
        $this->deletingLabel = $deviceType->name;
        $this->deletingWarning = $deviceType->broken_devices_count > 0
            ? __('home.device_type_delete_warning', ['count' => $deviceType->broken_devices_count])
            : __('home.device_type_delete_warning_empty');
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
        if ($this->deletingId) {
            DeviceType::findOrFail($this->deletingId)->delete();
            $this->reset('deletingId', 'deletingLabel', 'deletingWarning', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.device_type_deleted'));
        }
    }

    public function render()
    {
        return view('livewire.device-types.index', [
            'deviceTypes' => DeviceType::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(15),
            'isSuperAdmin' => Auth::user()?->hasRole('super-admin'),
        ]);
    }
}
