<?php

namespace App\Livewire\Users;

use App\Models\CorrespondenceEntity;
use App\Models\Governorate;
use App\Models\User;
use App\Support\PermissionGroups;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('إضافة مستخدم')]
class Create extends Component
{
    use Concerns\ResolvesRoleScopes;

    public string $name     = '';
    public string $username = '';
    public string $email    = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role     = '';

    /** نطاق المقرات */
    public array $selectedGovernorates = [];

    /** نطاق المراسلات */
    public string $correspondence_entity_id = '';
    public string $job_title = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);
    }

    public function save(): void
    {
        $this->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'username'             => ['required', 'string', 'max:255', 'unique:users,username'],
            'email'                => ['nullable', 'email', 'max:255'],
            'password'             => ['required', 'string', 'min:4', 'confirmed'],
            'role'                 => ['required', 'exists:roles,name'],
            'selectedGovernorates' => ['array'],
            ...$this->scopeRules(),
        ]);

        $scope = $this->resolvedScope();

        $user = User::create([
            'name'                     => $this->name,
            'username'                  => $this->username,
            'email'                     => $this->email ?: null,
            'password'                  => $this->password,
            'correspondence_entity_id'  => $scope['entity_id'],
            'job_title'                 => $scope['job_title'],
        ]);

        $user->assignRole($this->role);
        $user->governorates()->sync($scope['governorates']);

        Flux::toast(variant: 'success', text: __('home.user_created'));
        $this->redirect(route('users.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.users.create', [
            'roles'              => Role::all(),
            'governorates'       => Governorate::orderBy('order')->orderBy('id')->get(),
            'entities'           => CorrespondenceEntity::where('is_active', true)->orderBy('order')->orderBy('id')->get(),
            'needsGovernorates'  => PermissionGroups::needsGovernorates($this->rolePermissionNames()),
            'needsEntity'        => PermissionGroups::needsEntity($this->rolePermissionNames()),
        ]);
    }
}
