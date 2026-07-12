<?php

namespace App\Livewire\BuffetServices;

use App\Models\BuffetService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('خدمة البوفيه')]
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
        $item = BuffetService::findOrFail($id);
        $this->deletingId    = $item->id;
        $this->deletingLabel = $item->name;
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->can('offices.settings'), 403);
        if ($this->deletingId) {
            BuffetService::findOrFail($this->deletingId)->delete();
            $this->reset('deletingId', 'deletingLabel', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.buffet_service_deleted'));
        }
    }

    public function render()
    {
        return view('livewire.buffet-services.index', [
            'items' => BuffetService::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(15),
            'isSuperAdmin' => Auth::user()?->can('offices.settings'),
        ]);
    }
}
