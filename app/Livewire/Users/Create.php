<?php

namespace App\Livewire\Users;

use App\Models\Governorate;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('إضافة مستخدم')]
class Create extends Component
{
    public string $name     = '';
    public string $username = '';
    public string $email    = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role     = '';
    public array  $selectedGovernorates = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);
    }

    public function save(): void
    {
        $this->validate([
            'name'                => ['required', 'string', 'max:255'],
            'username'            => ['required', 'string', 'max:255', 'unique:users,username'],
            'email'               => ['nullable', 'email', 'max:255'],
            'password'            => ['required', 'string', 'min:4', 'confirmed'],
            'role'                => ['required', 'exists:roles,name'],
            'selectedGovernorates' => ['array'],
        ]);

        $user = User::create([
            'name'     => $this->name,
            'username' => $this->username,
            'email'    => $this->email ?: null,
            'password' => $this->password,
        ]);

        $user->assignRole($this->role);
        $user->governorates()->sync($this->selectedGovernorates);

        Flux::toast(variant: 'success', text: __('home.user_created'));
        $this->redirect(route('users.index'), navigate: true);
    }

    public function render()
    {
        $roles        = Role::all();
        $governorates = Governorate::oldest()->get();
        return view('livewire.users.create', compact('roles', 'governorates'));
    }
}
