<?php

namespace App\Livewire\Roles;

use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('تعديل الدور')]
class Edit extends Component
{
    public Role $role;
    public string $name = '';
    public array $selectedPermissions = [];

    public function mount(Role $role): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        $this->role               = $role;
        $this->name               = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
    }

    public function save(): void
    {
        $this->validate([
            'name'                => ['required', 'string', 'max:255', "unique:roles,name,{$this->role->id}"],
            'selectedPermissions' => ['array'],
        ]);

        if ($this->role->name !== 'super-admin') {
            $this->role->update(['name' => $this->name]);
        }

        $this->role->syncPermissions($this->selectedPermissions);

        Flux::toast(variant: 'success', text: __('home.role_updated'));
        $this->redirect(route('roles.index'), navigate: true);
    }

    public function render()
    {
        $permissions = Permission::all();
        return view('livewire.roles.edit', compact('permissions'));
    }
}
