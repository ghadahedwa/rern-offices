<?php

namespace App\Livewire\OfficeTypes;

use App\Models\OfficeType;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('أنواع المقرات')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(OfficeType $officeType): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
        $officeType->delete();
        Flux::toast(variant: 'success', text: __('home.office_type_deleted'));
    }

    public function render()
    {
        return view('livewire.office-types.index', [
            'officeTypes'  => OfficeType::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(15),
            'isSuperAdmin' => Auth::user()?->hasRole('super-admin'),
        ]);
    }
}
