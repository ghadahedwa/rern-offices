<?php

namespace App\Livewire\Governorates;

use App\Models\Governorate;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('المحافظات')]
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
        $governorate = Governorate::withCount('offices')->findOrFail($id);
        $this->deletingId    = $governorate->id;
        $this->deletingLabel = $governorate->name;
        $this->deletingWarning = $governorate->offices_count > 0
            ? __('home.governorate_delete_warning', ['count' => $governorate->offices_count])
            : __('home.governorate_delete_warning_empty');
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
        if ($this->deletingId) {
            Governorate::findOrFail($this->deletingId)->delete();
            $this->reset('deletingId', 'deletingLabel', 'deletingWarning', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.governorate_deleted'));
        }
    }

    public function render()
    {
        $isSuperAdmin = Auth::user()?->hasRole('super-admin');

        if ($isSuperAdmin) {
            $governorates = Governorate::withCount('offices')
                ->where('name', 'like', "%{$this->search}%")
                ->orderBy('order')->orderBy('id')
                ->paginate(10);
        } else {
            $governorates = auth()->user()
                ->governorates()
                ->withCount('offices')
                ->where('name', 'like', "%{$this->search}%")
                ->orderBy('order')->orderBy('id')
                ->paginate(10);
        }

        return view('livewire.governorates.index', [
            'governorates' => $governorates,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}
