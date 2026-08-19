<?php

namespace App\Livewire\Users;

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('المستخدمون')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $roleFilter = '';
    public string $entityFilter = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingEntityFilter(): void
    {
        $this->resetPage();
    }

    public function deleteUser(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->id === 1) {
            Flux::toast(variant: 'danger', text: __('home.cannot_delete_superadmin'));
            return;
        }

        if ($user->id === auth()->user()?->id) {
            Flux::toast(variant: 'danger', text: __('home.cannot_delete_self'));
            return;
        }

        $user->delete();
        Flux::toast(variant: 'success', text: __('home.user_deleted'));
    }

    public function render()
    {
        $roles    = Role::orderBy('name')->get();
        $entities = \App\Models\CorrespondenceEntity::orderBy('order')->orderBy('id')->get();

        $users = User::with(['roles', 'governorates', 'correspondenceEntity'])
            ->when($this->search, function ($q) {
                $like = '%'.\App\Support\ArabicText::normalize($this->search).'%';
                $q->where(function ($w) use ($like) {
                    $w->whereRaw(\App\Support\ArabicText::sqlNormalize('name').' LIKE ?', [$like])
                      ->orWhereRaw(\App\Support\ArabicText::sqlNormalize('username').' LIKE ?', [$like]);
                });
            })
            ->when($this->roleFilter, fn($q) => $q->role($this->roleFilter))
            ->when($this->entityFilter, fn($q) => $q->where('correspondence_entity_id', $this->entityFilter))
            ->oldest()
            ->paginate(10);

        return view('livewire.users.index', compact('users', 'roles', 'entities'));
    }
}
