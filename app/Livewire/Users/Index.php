<?php

namespace App\Livewire\Users;

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('المستخدمون')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
    }

    public function updatingSearch(): void
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
        $users = User::with(['roles', 'governorates'])
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('username', 'like', "%{$this->search}%");
            })
            ->oldest()
            ->paginate(10);

        return view('livewire.users.index', compact('users'));
    }
}
