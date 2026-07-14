<?php

namespace App\Livewire\Roles;

use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('الأدوار')]
class Index extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);
    }

    public function deleteRole(int $id): void
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'super-admin') {
            Flux::toast(variant: 'danger', text: __('home.cannot_delete_super_admin_role'));
            return;
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->event('deleted')
            ->withProperties(['name' => $role->name])
            ->log('حذف دور');

        $role->delete();
        Flux::toast(variant: 'success', text: __('home.role_deleted'));
    }

    public function render()
    {
        $roles = Role::with('permissions')->get();
        return view('livewire.roles.index', compact('roles'));
    }
}
