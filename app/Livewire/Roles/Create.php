<?php

namespace App\Livewire\Roles;

use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('إضافة دور')]
class Create extends Component
{
    public string $name = '';
    public array $selectedPermissions = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);
    }

    public function save(): void
    {
        $this->validate([
            'name'                => ['required', 'string', 'max:255', 'unique:roles,name'],
            'selectedPermissions' => ['array'],
        ]);

        $role = Role::create(['name' => $this->name, 'guard_name' => 'web']);
        $role->syncPermissions($this->selectedPermissions);

        Flux::toast(variant: 'success', text: __('home.role_created'));
        $this->redirect(route('roles.index'), navigate: true);
    }

    public function render()
    {
        $permissions = Permission::all();
        return view('livewire.roles.create', compact('permissions'));
    }
}
