<?php

namespace App\Livewire\Offices;

use App\Models\Governorate;
use App\Models\Office;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('المقرات')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    #[Url]
    public ?int $governorate_id = null;

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->hasRole('super-admin') || auth()->user()?->can('offices.index'),
            403
        );
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingGovernorateId(): void { $this->resetPage(); }

    public function render()
    {
        $query = Office::with(['governorate'])
            ->when($this->governorate_id, fn($q) => $q->where('governorate_id', $this->governorate_id))
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->latest();

        $user = auth()->user();
        $isSuperAdmin = $user?->hasRole('super-admin');

        return view('livewire.offices.index', [
            'offices'      => $query->paginate(10),
            'governorates' => Governorate::orderBy('id')->get(),
            'isSuperAdmin' => $isSuperAdmin,
            'canCreate'    => $isSuperAdmin || $user?->can('offices.create'),
            'canEdit'      => $isSuperAdmin || $user?->can('offices.edit'),
            'canDelete'    => $isSuperAdmin || $user?->can('offices.delete'),
        ]);
    }
}
