<?php

namespace App\Livewire\LocationDescriptions;

use App\Models\LocationDescription;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('وصف المقر')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(LocationDescription $locationDescription): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
        $locationDescription->delete();
        Flux::toast(variant: 'success', text: __('home.location_description_deleted'));
    }

    public function render()
    {
        return view('livewire.location-descriptions.index', [
            'items'        => LocationDescription::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(15),
            'isSuperAdmin' => Auth::user()?->hasRole('super-admin'),
        ]);
    }
}
