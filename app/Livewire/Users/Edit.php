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
#[Title('تعديل مستخدم')]
class Edit extends Component
{
    use Concerns\ResolvesRoleScopes;

    public User $user;

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

    public function mount(User $user): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        if ($user->id === 1) {
            Flux::toast(variant: 'danger', text: __('home.cannot_edit_superadmin'));
            $this->redirect(route('users.index'), navigate: true);
            return;
        }

        $this->user                     = $user;
        $this->name                     = $user->name;
        $this->username                 = $user->username ?? '';
        $this->email                    = $user->email ?? '';
        $this->role                     = $user->roles->first()?->name ?? '';
        $this->selectedGovernorates     = $user->governorates->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        $this->correspondence_entity_id = (string) ($user->correspondence_entity_id ?? '');
        $this->job_title                = $user->job_title ?? '';
    }

    public function save(): void
    {
        $rules = [
            'name'                 => ['required', 'string', 'max:255'],
            'username'             => ['required', 'string', 'max:255', "unique:users,username,{$this->user->id}"],
            'email'                => ['nullable', 'email', 'max:255'],
            'role'                 => ['required', 'exists:roles,name'],
            'selectedGovernorates' => ['array'],
            ...$this->scopeRules(),
        ];

        if ($this->password) {
            $rules['password'] = ['string', 'min:4', 'confirmed'];
        }

        $this->validate($rules);

        $scope = $this->resolvedScope();

        $data = [
            'name'                     => $this->name,
            'username'                 => $this->username,
            'email'                    => $this->email ?: null,
            'correspondence_entity_id' => $scope['entity_id'],
            'job_title'                => $scope['job_title'],
        ];

        if ($this->password) {
            $data['password'] = $this->password;
        }

        $this->user->update($data);
        $this->user->syncRoles([$this->role]);
        $this->user->governorates()->sync($scope['governorates']);

        Flux::toast(variant: 'success', text: __('home.user_updated'));
        $this->redirect(route('users.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.users.edit', [
            'roles'             => Role::all(),
            'governorates'      => Governorate::orderBy('order')->orderBy('id')->get(),
            'entities'          => CorrespondenceEntity::where('is_active', true)->orderBy('order')->orderBy('id')->get(),
            'needsGovernorates' => PermissionGroups::needsGovernorates($this->rolePermissionNames()),
            'needsEntity'       => PermissionGroups::needsEntity($this->rolePermissionNames()),
        ]);
    }
}
