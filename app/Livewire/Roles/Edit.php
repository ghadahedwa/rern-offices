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
    public int $level = 1;
    public array $selectedPermissions = [];

    public function mount(Role $role): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        $this->role                = $role;
        $this->name                = $role->name;
        $this->level               = $role->level ?? 1;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
    }

    public function save(): void
    {
        $this->validate([
            'name'                => ['required', 'string', 'max:255', "unique:roles,name,{$this->role->id}"],
            'level'               => ['required', 'integer', 'between:1,3'],
            'selectedPermissions' => ['array'],
        ]);

        if ($this->role->name !== 'super-admin') {
            $this->role->name = $this->name;
        }

        $this->role->level = $this->level;
        $this->role->save();

        $this->role->syncPermissions($this->selectedPermissions);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($this->role)
            ->event('updated')
            ->withProperties(['name' => $this->role->name])
            ->log('تعديل دور');

        Flux::toast(variant: 'success', text: __('home.role_updated'));
        $this->redirect(route('roles.index'), navigate: true);
    }

    public function render()
    {
        $this->ensurePermissions();
        $permissions = Permission::all();
        return view('livewire.roles.edit', compact('permissions'));
    }

    private function ensurePermissions(): void
    {
        foreach (['vehicles.index', 'vehicles.view', 'vehicles.create', 'vehicles.edit', 'vehicles.delete', 'vehicles.export', 'offices.phone-directory', 'governorates.index', 'governorates.create', 'governorates.edit', 'governorates.delete'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
