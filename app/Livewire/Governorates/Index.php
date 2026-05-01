<?php

namespace App\Livewire\Governorates;

use App\Models\Governorate;
use Flux\Flux;
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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function deleteGovernorate(int $id): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        Governorate::findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('home.governorate_deleted'));
    }

    public function render()
    {
        $isSuperAdmin = auth()->user()?->hasRole('super-admin');

        if ($isSuperAdmin) {
            $governorates = Governorate::withCount('offices')
                ->where('name', 'like', "%{$this->search}%")
                ->oldest()
                ->paginate(10);
        } else {
            $governorates = auth()->user()
                ->governorates()
                ->withCount('offices')
                ->where('name', 'like', "%{$this->search}%")
                ->oldest()
                ->paginate(10);
        }

        return view('livewire.governorates.index', [
            'governorates' => $governorates,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}
