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
#[Title('تعديل مستخدم')]
class Edit extends Component
{
    public User $user;

    public string $name     = '';
    public string $username = '';
    public string $email    = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role     = '';
    public array  $selectedGovernorates = [];

    public function mount(User $user): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        if ($user->id === 1) {
            Flux::toast(variant: 'danger', text: __('home.cannot_edit_superadmin'));
            $this->redirect(route('users.index'), navigate: true);
            return;
        }

        $this->user                = $user;
        $this->name                = $user->name;
        $this->username            = $user->username ?? '';
        $this->email               = $user->email ?? '';
        $this->role                = $user->roles->first()?->name ?? '';
        $this->selectedGovernorates = $user->governorates->pluck('id')->map(fn($id) => (string) $id)->toArray();
    }

    public function save(): void
    {
        $rules = [
            'name'                => ['required', 'string', 'max:255'],
            'username'            => ['required', 'string', 'max:255', "unique:users,username,{$this->user->id}"],
            'email'               => ['nullable', 'email', 'max:255'],
            'role'                => ['required', 'exists:roles,name'],
            'selectedGovernorates' => ['array'],
        ];

        if ($this->password) {
            $rules['password'] = ['string', 'min:4', 'confirmed'];
        }

        $this->validate($rules);

        $data = [
            'name'     => $this->name,
            'username' => $this->username,
            'email'    => $this->email ?: null,
        ];

        if ($this->password) {
            $data['password'] = $this->password;
        }

        $this->user->update($data);
        $this->user->syncRoles([$this->role]);
        $this->user->governorates()->sync($this->selectedGovernorates);

        Flux::toast(variant: 'success', text: __('home.user_updated'));
        $this->redirect(route('users.index'), navigate: true);
    }

    public function render()
    {
        $roles        = Role::all();
        $governorates = Governorate::oldest()->get();
        return view('livewire.users.edit', compact('roles', 'governorates'));
    }
}
