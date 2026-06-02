<?php

namespace App\Livewire\Offices;

use App\Models\Governorate;
use App\Models\LocationDescription;
use App\Models\Office;
use App\Models\OfficeType;
use Flux\Flux;
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

    #[Url]
    public ?int $type_id = null;

    #[Url]
    public ?int $location_description_id = null;

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->hasRole('super-admin') || auth()->user()?->can('offices.index'),
            403
        );
    }

    public function delete(Office $office): void
    {
        abort_unless(
            auth()->user()?->hasRole('super-admin') || auth()->user()?->can('offices.delete'),
            403
        );
        $office->delete();
        Flux::toast(variant: 'success', text: __('home.office_deleted'));
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingGovernorateId(): void { $this->resetPage(); }
    public function updatingTypeId(): void { $this->resetPage(); }
    public function updatingLocationDescriptionId(): void { $this->resetPage(); }

    public function render()
    {
        $user         = auth()->user();
        $isSuperAdmin = $user?->hasRole('super-admin');

        $governorates = $isSuperAdmin
            ? Governorate::orderBy('order')->orderBy('id')->get()
            : $user->governorates()->orderBy('order')->orderBy('id')->get();

        $allowedGovIds = $isSuperAdmin ? null : $governorates->pluck('id');

        $query = Office::with(['governorate', 'officeType', 'locationDescription'])
            ->when($allowedGovIds, fn($q) => $q->whereIn('governorate_id', $allowedGovIds))
            ->when($this->governorate_id, fn($q) => $q->where('governorate_id', $this->governorate_id))
            ->when($this->type_id, fn($q) => $q->where('type_id', $this->type_id))
            ->when($this->location_description_id, fn($q) => $q->where('location_description_id', $this->location_description_id))
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->latest();

        return view('livewire.offices.index', [
            'offices'               => $query->paginate(10),
            'governorates'          => $governorates,
            'officeTypes'           => OfficeType::orderBy('name')->get(),
            'locationDescriptions'  => LocationDescription::orderBy('name')->get(),
            'isSuperAdmin' => $isSuperAdmin,
            'canCreate'    => $isSuperAdmin || $user?->can('offices.create'),
            'canView'      => $isSuperAdmin || $user?->can('offices.view'),
            'canEdit'      => $isSuperAdmin || $user?->can('offices.edit'),
            'canDelete'    => $isSuperAdmin || $user?->can('offices.delete'),
        ]);
    }
}
