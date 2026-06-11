<?php

namespace App\Livewire\WorkSystems;

use App\Models\WorkSystem;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('أنظمة العمل')]
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
        $workSystem = WorkSystem::findOrFail($id);
        $this->deletingId    = $workSystem->id;
        $this->deletingLabel = $workSystem->name;
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
        if ($this->deletingId) {
            WorkSystem::findOrFail($this->deletingId)->delete();
            $this->reset('deletingId', 'deletingLabel', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.work_system_deleted'));
        }
    }

    public function render()
    {
        return view('livewire.work-systems.index', [
            'workSystems'  => WorkSystem::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(15),
            'isSuperAdmin' => Auth::user()?->hasRole('super-admin'),
        ]);
    }
}
