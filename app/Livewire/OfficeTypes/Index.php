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

    public bool $showDelete = false;
    public ?int $deletingId = null;
    public string $deletingLabel = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function askDelete(int $id): void
    {
        abort_unless(Auth::user()?->can('offices.settings'), 403);
        $officeType = OfficeType::findOrFail($id);
        $this->deletingId    = $officeType->id;
        $this->deletingLabel = $officeType->name;
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->can('offices.settings'), 403);
        if ($this->deletingId) {
            OfficeType::findOrFail($this->deletingId)->delete();
            $this->reset('deletingId', 'deletingLabel', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.office_type_deleted'));
        }
    }

    public function render()
    {
        return view('livewire.office-types.index', [
            'officeTypes'  => OfficeType::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(15),
            'isSuperAdmin' => Auth::user()?->can('offices.settings'),
        ]);
    }
}
